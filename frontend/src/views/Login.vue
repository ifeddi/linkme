<template>
  <div class="login">
    <h2>Login</h2>
    <form @submit.prevent="handleLogin">
      <input v-model="email" placeholder="Email" required />
      <input v-model="password" type="password" placeholder="Password" required />
      <button type="submit">Login</button>
      <p v-if="error" style="color: red;">{{ error }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";

const email = ref("");
const password = ref("");
const error = ref(null);
const auth = useAuthStore();
const router = useRouter();

const handleLogin = async () => {
  const success = await auth.login(email.value, password.value);
  if (success) {
    router.push("/");
  } else {
    error.value = "Invalid credentials";
  }
};
</script>
