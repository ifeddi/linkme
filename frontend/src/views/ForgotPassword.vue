<template>
  <div class="forgot-password-container">
    <div class="forgot-password-card">
      <div class="card-header">
        <h1>Forgot Password</h1>
        <p>Enter your email address and we'll send you a link to reset your password.</p>
      </div>
      
      <div class="card-body">
        <!-- Success state -->
        <div v-if="isSuccess" class="success-state">
          <div class="success-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22,4 12,14.01 9,11.01"></polyline>
            </svg>
          </div>
          <h2>Email Sent!</h2>
          <p>If an account with that email exists, we've sent you a password reset link.</p>
          <p class="email-display">Check your email: <strong>{{ submittedEmail }}</strong></p>
          <div class="success-actions">
            <button @click="goToLogin" class="btn btn-primary">Back to Login</button>
            <button @click="resetForm" class="btn btn-secondary">Send Another Email</button>
          </div>
        </div>
        
        <!-- Form state -->
        <div v-else class="form-state">
          <form @submit.prevent="handleForgotPassword">
            <div class="form-group">
              <label for="email">Email Address</label>
              <input 
                id="email"
                v-model="formData.email"
                type="email" 
                placeholder="Enter your email address"
                class="form-input"
                :class="{ 'error': errors.email }"
                required
              />
              <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
            </div>
            
            <button 
              type="submit" 
              class="btn btn-primary btn-full"
              :disabled="!isFormValid || isSubmitting"
            >
              <span v-if="isSubmitting">Sending...</span>
              <span v-else>Send Reset Link</span>
            </button>
          </form>
          
          <div class="form-footer">
            <p>Remember your password? <router-link to="/login" class="login-link">Back to Login</router-link></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'

const router = useRouter()

const isSuccess = ref(false)
const isSubmitting = ref(false)
const submittedEmail = ref('')

const formData = ref({
  email: ''
})

const errors = ref({
  email: ''
})

const isFormValid = computed(() => {
  return formData.value.email && 
         formData.value.email.includes('@') &&
         formData.value.email.length > 5
})

const validateForm = () => {
  errors.value = {
    email: ''
  }
  
  if (!formData.value.email) {
    errors.value.email = 'Email is required'
    return false
  }
  
  if (!formData.value.email.includes('@')) {
    errors.value.email = 'Please enter a valid email address'
    return false
  }
  
  return true
}

const handleForgotPassword = async () => {
  if (!validateForm()) {
    return
  }
  
  isSubmitting.value = true
  
  try {
    const response = await api.post('/api/user/forgot-password', {
      email: formData.value.email
    })
    
    if (response.status === 200) {
      submittedEmail.value = formData.value.email
      isSuccess.value = true
    }
  } catch (err) {
    console.error('Forgot password error:', err)
    // Even on error, we show success for security (don't reveal if email exists)
    submittedEmail.value = formData.value.email
    isSuccess.value = true
  } finally {
    isSubmitting.value = false
  }
}

const resetForm = () => {
  isSuccess.value = false
  formData.value.email = ''
  errors.value.email = ''
}

const goToLogin = () => {
  router.push('/login')
}
</script>

<style scoped>
.forgot-password-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.forgot-password-card {
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
  margin: 0 0 10px 0;
  font-size: 24px;
  font-weight: 600;
}

.card-header p {
  margin: 0;
  font-size: 14px;
  opacity: 0.9;
}

.card-body {
  padding: 40px 30px;
}

/* Success state */
.success-state {
  text-align: center;
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

.email-display {
  color: #666;
  font-size: 14px;
  margin: 0;
}

.success-actions {
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

.form-footer {
  text-align: center;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e1e5e9;
}

.form-footer p {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.login-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}

.login-link:hover {
  text-decoration: underline;
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
  .forgot-password-container {
    padding: 16px;
  }
  
  .card-header {
    padding: 24px 20px;
  }
  
  .card-body {
    padding: 30px 20px;
  }
  
  .success-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .success-actions .btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .container {
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
}
</style>
