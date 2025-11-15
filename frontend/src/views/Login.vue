<template>
  <div class="auth-container">
    <div class="card">
      <img
        id="profile-img"
        src="//ssl.gstatic.com/accounts/ui/avatar_2x.png"
        class="profile-img-card"
      />
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Email</label>
          <input v-model="email" type="email" placeholder="Email" required />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input v-model="password" type="password" placeholder="Password" required />
        </div>
        <div class="forgot-password-link">
          <router-link to="/forgot-password">Forgot your password?</router-link>
        </div>
        <button type="submit" class="btn primary">Login</button>
        <p v-if="error" class="alert error">{{ error }}</p>
      </form>
      <p class="link">
        No account?
        <router-link to="/register">Register</router-link>
      </p>
    </div>
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
  try {
    const success = await auth.login(email.value, password.value);
    if (success) {
      router.push("/");
    } else {
      error.value = "Invalid credentials";
    }
  } catch (err) {
    error.value = err.message || "Login failed";
  }
};
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: #eef1f5;
}

.card {
  width: 350px;
  background: #fff;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.profile-img-card {
  width: 90px;
  height: 90px;
  margin: 0 auto 20px;
  border-radius: 50%;
}

.form-group {
  margin-bottom: 15px;
  text-align: left;
}

label {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 5px;
  display: block;
}

input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
  outline: none;
}

input:focus {
  border-color: #007bff;
}

.btn {
  width: 100%;
  padding: 10px;
  border: none;
  border-radius: 5px;
  color: white;
  font-weight: bold;
  cursor: pointer;
  margin-top: 10px;
}

.btn.primary {
  background: #007bff;
}

.btn.primary:hover {
  background: #0056b3;
}

.alert {
  margin-top: 10px;
  padding: 10px;
  border-radius: 4px;
  font-size: 14px;
}

.alert.error {
  background: #f8d7da;
  color: #721c24;
}

.forgot-password-link {
  text-align: right;
  margin-bottom: 15px;
}

.forgot-password-link a {
  color: #007bff;
  text-decoration: none;
  font-size: 14px;
}

.forgot-password-link a:hover {
  text-decoration: underline;
}

.link {
  margin-top: 15px;
  font-size: 14px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .auth-container {
    padding: 20px 15px;
    min-height: calc(100vh - 50px);
  }
  
  .auth-card {
    width: 100%;
    max-width: 400px;
    padding: 30px 20px;
  }
  
  .auth-header h1 {
    font-size: 1.8rem;
    margin-bottom: 8px;
  }
  
  .auth-header p {
    font-size: 0.9rem;
  }
  
  .form-group {
    margin-bottom: 18px;
  }
  
  .form-group label {
    font-size: 14px;
    margin-bottom: 6px;
  }
  
  .form-control {
    padding: 12px 14px;
    font-size: 16px; /* Évite le zoom sur iOS */
  }
  
  .btn {
    padding: 12px 20px;
    font-size: 15px;
  }
  
  .forgot-password-link {
    margin-bottom: 12px;
  }
  
  .forgot-password-link a {
    font-size: 13px;
  }
  
  .link {
    font-size: 13px;
  }
}

@media (max-width: 640px) {
  .auth-container {
    padding: 15px 10px;
  }
  
  .auth-card {
    padding: 25px 15px;
  }
  
  .auth-header h1 {
    font-size: 1.6rem;
  }
  
  .auth-header p {
    font-size: 0.85rem;
  }
  
  .form-control {
    padding: 11px 12px;
    font-size: 15px;
  }
  
  .btn {
    padding: 11px 18px;
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  .auth-container {
    padding: 10px 8px;
  }
  
  .auth-card {
    padding: 20px 12px;
    border-radius: 8px;
  }
  
  .auth-header h1 {
    font-size: 1.4rem;
  }
  
  .auth-header p {
    font-size: 0.8rem;
  }
  
  .form-group {
    margin-bottom: 15px;
  }
  
  .form-control {
    padding: 10px 11px;
    font-size: 14px;
  }
  
  .btn {
    padding: 10px 16px;
    font-size: 13px;
  }
  
  .alert {
    padding: 8px;
    font-size: 13px;
  }
}
</style>
