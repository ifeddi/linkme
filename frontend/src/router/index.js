import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";
import Login from "../views/Login.vue";
import Home from "../views/Home.vue";
import Register from "../views/Register.vue";
import Profile from "../views/Profile.vue";
import UserProfile from "../views/UserProfile.vue";
import VerifyEmail from "../views/VerifyEmail.vue";
import ForgotPassword from "../views/ForgotPassword.vue";
import ResetPassword from "../views/ResetPassword.vue";

const routes = [
	{ path: "/", component: Home, meta: { requiresAuth: true } },
	{ path: "/login", component: Login, meta: { requiresGuest: true } },
	{ path: "/register", component: Register, meta: { requiresGuest: true } },
  { path: "/profile", component: Profile, meta: { requiresAuth: true } },
  { path: "/u/:username", component: UserProfile, meta: { requiresAuth: true } },
  { path: "/verify-email", component: VerifyEmail, meta: { requiresGuest: true } },
  { path: "/forgot-password", component: ForgotPassword, meta: { requiresGuest: true } },
  { path: "/reset-password", component: ResetPassword, meta: { requiresGuest: true } }
];

const router = createRouter({
	history: createWebHistory(),
	routes,
});

router.beforeEach((to, from, next) => {
	const authStore = useAuthStore();
	const isLoggedIn = authStore.isLoggedIn;
	
	// Pages qui nécessitent une authentification
	if (to.meta.requiresAuth) {
		if (!isLoggedIn) {
			// Pas connecté, rediriger vers login
			next("/login");
			return;
		}
		// Connecté, autoriser l'accès
		next();
		return;
	}
	
	// Pages qui nécessitent d'être un invité (non connecté)
	if (to.meta.requiresGuest) {
		if (isLoggedIn) {
			// Déjà connecté, rediriger vers la page d'accueil
			next("/");
			return;
		}
		// Pas connecté, autoriser l'accès aux pages login/register
		next();
		return;
	}
	
	// Pour toutes les autres pages, vérifier l'authentification
	if (!isLoggedIn) {
		next("/login");
		return;
	}
	
	next();
});

export default router;
