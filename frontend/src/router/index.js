import { createRouter, createWebHistory } from "vue-router";
import Login from "../views/Login.vue";
import Home from "../views/Home.vue";

const routes = [
	{ path: "/", component: Home, meta: { requiresAuth: true } },
	{ path: "/login", component: Login }
];

const router = createRouter({
	history: createWebHistory(),
	routes,
});

router.beforeEach((to, from, next) => {
	const token = localStorage.getItem("token");
	if (to.meta.requiresAuth && !token) {
		next("/login");
	} else {
		next();
	}
});

export default router;
