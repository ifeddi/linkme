<template>
  <div class="auth-container">
    <div class="card">
      <img
        id="profile-img"
        src="//ssl.gstatic.com/accounts/ui/avatar_2x.png"
        class="profile-img-card"
      />
      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label for="email">Email</label>
          <input v-model="email" type="email" placeholder="Email" required />
        </div>
        <div class="form-group">
          <label for="username">Username</label>
          <input v-model="username" type="text" placeholder="Username" required />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input v-model="password" type="password" placeholder="Password" required />
        </div>
        <button type="submit" class="btn success" :disabled="loading">
          <span v-if="loading">Registering…</span>
          <span v-else>Register</span>
        </button>
        <p v-if="error" class="alert error">{{ error }}</p>
        <p v-if="success" class="alert success">{{ successMessage }}</p>
        <p class="link">
          Already have an account? <router-link to="/login">Login</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { register as apiRegister } from "../api";

const email = ref("");
const username = ref("");
const password = ref("");
const error = ref(null);
const success = ref(false);
const successMessage = ref("");
const loading = ref(false);
const router = useRouter();

const handleRegister = async () => {
  error.value = null;
  success.value = false;
  loading.value = true;
  try {
    const response = await apiRegister(password.value, email.value, username.value);
    success.value = true;
    successMessage.value = response.data.message || "Account created successfully! Please check your email to verify your account.";
    // Don't redirect automatically, let user see the message
  } catch (e) {
    error.value = e?.response?.data?.message || "Registration failed";
  } finally {
    loading.value = false;
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
  border-color: #28a745;
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

.btn.success {
  background: #28a745;
}

.btn.success:hover {
  background: #1e7e34;
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

.alert.success {
  background: #d4edda;
  color: #155724;
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
  
  .alert {
    font-size: 13px;
    padding: 8px;
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
    padding: 7px;
    font-size: 12px;
  }
}
</style>
