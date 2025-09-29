import { defineStore } from "pinia";
import axios from "axios";
import { api } from "../api";

export const useAuthStore = defineStore("auth", {
	state: () => ({
		token: localStorage.getItem("token") || null,
		user: null,
		isAuthenticated: false,
		isLoading: false,
	}),
	
	getters: {
		// Vérifier si l'utilisateur est authentifié
		isLoggedIn: (state) => {
			return !!state.token && state.isAuthenticated;
		}
	},
	
	actions: {
		// Initialiser l'authentification au démarrage
		async initAuth() {
			const token = localStorage.getItem("token");
			const userEmail = localStorage.getItem("userEmail");
			const savedProfile = localStorage.getItem("userProfile");
			
			if (token) {
				this.token = token;
				this.isAuthenticated = true;
				this.user = { email: userEmail };
				
				// Charger le profil sauvegardé d'abord
				if (savedProfile) {
					try {
						const profileData = JSON.parse(savedProfile);
						this.user = {
							...this.user,
							...profileData
						};
					} catch (e) {
						console.warn("Could not parse saved profile:", e);
					}
				}
				
				// Récupérer les données complètes du profil
				try {
					const profileResponse = await api.get("/api/user/profile");
					this.user = {
						...this.user,
						...profileResponse.data
					};
					// Mettre à jour le localStorage
					localStorage.setItem('userProfile', JSON.stringify(profileResponse.data));
				} catch (profileError) {
					console.warn("Could not load profile data on init:", profileError);
					// On continue même si le profil ne peut pas être chargé
				}
			}
		},
		
		async login(email, password) {
			this.isLoading = true;
			try {
				const response = await axios.post("http://localhost:8000/api/login_check", {
					email,
					password,
				});
				
				this.token = response.data.token;
				this.isAuthenticated = true;
				this.user = { email: email }; // Stocker l'email de l'utilisateur
				localStorage.setItem("token", this.token);
				localStorage.setItem("userEmail", email); // Sauvegarder l'email dans localStorage
				
				// Récupérer les données complètes du profil après login
				try {
					const profileResponse = await api.get("/api/user/profile");
					this.user = {
						...this.user,
						...profileResponse.data
					};
					// Sauvegarder les données du profil dans localStorage
					localStorage.setItem('userProfile', JSON.stringify(profileResponse.data));
				} catch (profileError) {
					console.warn("Could not load profile data after login:", profileError);
					// On continue même si le profil ne peut pas être chargé
				}
				
				return true;
			} catch (error) {
				console.error("Login failed", error);
				this.logout();
				
				// Check if it's an email verification error
				if (error.response?.data?.message?.includes('verify your email')) {
					throw new Error('Please verify your email address before logging in.');
				}
				
				return false;
			} finally {
				this.isLoading = false;
			}
		},
		
		logout() {
			this.token = null;
			this.user = null;
			this.isAuthenticated = false;
			localStorage.removeItem("token");
			localStorage.removeItem("userEmail");
			localStorage.removeItem("userProfile");
		},
		
		// Vérifier la validité du token
		async checkAuth() {
			if (!this.token) {
				this.logout();
				return false;
			}
			
			try {
				// Optionnel: vérifier avec le backend si le token est toujours valide
				// const response = await api.get("/api/user/profile");
				// this.user = response.data;
				this.isAuthenticated = true;
				return true;
			} catch (error) {
				console.error("Token validation failed", error);
				this.logout();
				return false;
			}
		},

		// Mettre à jour le profil utilisateur
		updateUserProfile(profileData) {
			this.user = {
				...this.user,
				...profileData
			};
			// Sauvegarder dans localStorage
			localStorage.setItem("userProfile", JSON.stringify(this.user));
		},

		// Mettre à jour le statut en ligne
		async updateOnlineStatus() {
			try {
				const { updateOnlineStatus } = await import('../api');
				await updateOnlineStatus();
			} catch (error) {
				console.error('Error updating online status:', error);
			}
		}
	},
});
