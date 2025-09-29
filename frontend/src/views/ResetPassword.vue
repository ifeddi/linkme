<template>
  <div class="reset-password-container">
    <div class="reset-password-card">
      <div class="card-header">
        <h1>Reset Password</h1>
      </div>
      
      <div class="card-body">
        <!-- Loading state -->
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Resetting your password...</p>
        </div>
        
        <!-- Success state -->
        <div v-else-if="isSuccess" class="success-state">
          <div class="success-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22,4 12,14.01 9,11.01"></polyline>
            </svg>
          </div>
          <h2>Password Reset Successfully!</h2>
          <p>Your password has been updated successfully.</p>
          <p class="redirect-info">You will be redirected to the login page in {{ countdown }} seconds...</p>
          <button @click="goToLogin" class="btn btn-primary">Go to Login Now</button>
        </div>
        
        <!-- Error state -->
        <div v-else-if="error" class="error-state">
          <div class="error-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="15" y1="9" x2="9" y2="15"></line>
              <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
          </div>
          <h2>Reset Failed</h2>
          <p class="error-message">{{ error }}</p>
          <div class="error-actions">
            <button @click="goToLogin" class="btn btn-primary">Go to Login</button>
            <button @click="goToForgotPassword" class="btn btn-secondary">Request New Link</button>
          </div>
        </div>
        
        <!-- Form state -->
        <div v-else class="form-state">
          <form @submit.prevent="handleResetPassword">
            <div class="form-group">
              <label for="password">New Password</label>
              <input 
                id="password"
                v-model="formData.password"
                type="password" 
                placeholder="Enter your new password"
                class="form-input"
                :class="{ 'error': errors.password }"
                required
              />
              <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
            </div>
            
            <div class="form-group">
              <label for="confirmPassword">Confirm New Password</label>
              <input 
                id="confirmPassword"
                v-model="formData.confirmPassword"
                type="password" 
                placeholder="Confirm your new password"
                class="form-input"
                :class="{ 'error': errors.confirmPassword }"
                required
              />
              <span v-if="errors.confirmPassword" class="error-text">{{ errors.confirmPassword }}</span>
            </div>
            
            <button 
              type="submit" 
              class="btn btn-primary btn-full"
              :disabled="!isFormValid || isSubmitting"
            >
              <span v-if="isSubmitting">Resetting...</span>
              <span v-else>Reset Password</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'

const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isSuccess = ref(false)
const isSubmitting = ref(false)
const error = ref('')
const countdown = ref(5)
let countdownInterval = null

const formData = ref({
  password: '',
  confirmPassword: ''
})

const errors = ref({
  password: '',
  confirmPassword: ''
})

const isFormValid = computed(() => {
  return formData.value.password && 
         formData.value.confirmPassword && 
         formData.value.password === formData.value.confirmPassword &&
         formData.value.password.length >= 6
})

const validateForm = () => {
  errors.value = {
    password: '',
    confirmPassword: ''
  }
  
  if (!formData.value.password) {
    errors.value.password = 'Password is required'
    return false
  }
  
  if (formData.value.password.length < 6) {
    errors.value.password = 'Password must be at least 6 characters'
    return false
  }
  
  if (!formData.value.confirmPassword) {
    errors.value.confirmPassword = 'Please confirm your password'
    return false
  }
  
  if (formData.value.password !== formData.value.confirmPassword) {
    errors.value.confirmPassword = 'Passwords do not match'
    return false
  }
  
  return true
}

const handleResetPassword = async () => {
  if (!validateForm()) {
    return
  }
  
  const token = route.query.token
  
  if (!token) {
    error.value = 'No reset token provided'
    return
  }
  
  isSubmitting.value = true
  
  try {
    const response = await api.post('/api/user/reset-password', {
      token: token,
      password: formData.value.password
    })
    
    if (response.status === 200) {
      isSuccess.value = true
      startCountdown()
    }
  } catch (err) {
    console.error('Password reset error:', err)
    error.value = err.response?.data?.message || 'Failed to reset password'
  } finally {
    isSubmitting.value = false
  }
}

const startCountdown = () => {
  countdownInterval = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownInterval)
      goToLogin()
    }
  }, 1000)
}

const goToLogin = () => {
  router.push('/login')
}

const goToForgotPassword = () => {
  router.push('/forgot-password')
}

onMounted(() => {
  const token = route.query.token
  if (!token) {
    error.value = 'No reset token provided'
  }
})

onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }
})
</script>

<style scoped>
.reset-password-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.reset-password-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 500px;
  overflow: hidden;
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 30px;
  text-align: center;
}

.card-header h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.card-body {
  padding: 40px 30px;
  text-align: center;
}

/* Loading state */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-state p {
  color: #666;
  font-size: 16px;
  margin: 0;
}

/* Success state */
.success-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.success-icon {
  color: #28a745;
}

.success-state h2 {
  color: #28a745;
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.success-state p {
  color: #333;
  font-size: 16px;
  margin: 0;
}

.redirect-info {
  color: #666;
  font-size: 14px;
  margin: 0;
}

/* Error state */
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.error-icon {
  color: #dc3545;
}

.error-state h2 {
  color: #dc3545;
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.error-message {
  color: #666;
  font-size: 16px;
  margin: 0;
}

.error-actions {
  display: flex;
  gap: 12px;
  margin-top: 10px;
}

/* Form state */
.form-state {
  text-align: left;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #333;
}

.form-input {
  width: 100%;
  padding: 12px;
  border: 2px solid #e1e5e9;
  border-radius: 6px;
  font-size: 16px;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
}

.form-input.error {
  border-color: #dc3545;
}

.error-text {
  color: #dc3545;
  font-size: 14px;
  margin-top: 4px;
  display: block;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-block;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5a6fd8;
}

.btn-primary:disabled {
  background: #c7c7c7;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-full {
  width: 100%;
}

/* Responsive */
@media (max-width: 768px) {
  .reset-password-container {
    padding: 16px;
  }
  
  .card-header {
    padding: 24px 20px;
  }
  
  .card-body {
    padding: 30px 20px;
  }
  
  .error-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .error-actions .btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .reset-password-container {
    padding: 10px;
  }
  
  .card {
    margin: 10px 0;
    border-radius: 8px;
  }
  
  .card-header {
    padding: 20px 16px;
  }
  
  .card-header h1 {
    font-size: 1.4rem;
  }
  
  .card-body {
    padding: 25px 16px;
  }
  
  .form-control {
    padding: 10px 11px;
    font-size: 16px; /* Évite le zoom sur iOS */
  }
  
  .btn {
    padding: 10px 16px;
    font-size: 13px;
  }
  
  .success-icon {
    width: 60px;
    height: 60px;
    font-size: 30px;
  }
  
  .success-message h2 {
    font-size: 1.3rem;
  }
  
  .success-message p {
    font-size: 0.9rem;
  }
  
  .password-requirements {
    font-size: 0.8rem;
  }
}
</style>
