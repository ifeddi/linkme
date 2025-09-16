import { defineStore } from "pinia";
import axios from "axios";

export const useAuthStore = defineStore("auth", {
	state: () => ({
		token: localStorage.getItem("token") || null,
		user: null,
	}),
	actions: {
		async login(email, password) {
			try {
				const response = await axios.post("http://localhost:8000/api/login_check", {
					email,
					password,
				});
				this.token = response.data.token;
				localStorage.setItem("token", this.token);

				axios.defaults.headers.common["Authorization"] = `Bearer ${this.token}`;
				return true;
			} catch (error) {
				console.error("Login failed", error);
				return false;
			}
		},
		logout() {
			this.token = null;
			localStorage.removeItem("token");
			delete axios.defaults.headers.common["Authorization"];
		},
	},
});
