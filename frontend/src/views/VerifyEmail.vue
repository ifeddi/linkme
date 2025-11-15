<template>
  <div class="verify-email-container">
    <div class="verify-email-card">
      <div class="card-header">
        <h1>Email Verification</h1>
      </div>
      
      <div class="card-body">
        <!-- Loading state -->
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Verifying your email...</p>
        </div>
        
        <!-- Success state -->
        <div v-else-if="isSuccess" class="success-state">
          <div class="success-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22,4 12,14.01 9,11.01"></polyline>
            </svg>
          </div>
          <h2>Email Verified Successfully!</h2>
          <p class="email-display">Your email <strong>{{ verifiedEmail }}</strong> has been verified.</p>
          <p class="redirect-info">You will be redirected to the login page in {{ countdown }} seconds...</p>
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
          <h2>Verification Failed</h2>
          <p class="error-message">{{ error }}</p>
          <div class="error-actions">
            <button @click="goToLogin" class="btn btn-primary">Go to Login</button>
            <button @click="goToRegister" class="btn btn-secondary">Register Again</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'

const route = useRoute()
const router = useRouter()

const isLoading = ref(true)
const isSuccess = ref(false)
const error = ref('')
const verifiedEmail = ref('')
const countdown = ref(10)
let countdownInterval = null

const verifyEmail = async () => {
  const token = route.query.token
  
  if (!token) {
    error.value = 'No verification token provided'
    isLoading.value = false
    return
  }
  
  try {
    const response = await api.get(`/api/user/verify-email?token=${token}`)
    
    if (response.status === 200) {
      isSuccess.value = true
      verifiedEmail.value = response.data.email
      isLoading.value = false
      
      // Start countdown
      startCountdown()
    }
  } catch (err) {
    console.error('Email verification error:', err)
    error.value = err.response?.data?.message || 'Failed to verify email'
    isLoading.value = false
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

const goToRegister = () => {
  router.push('/register')
}

onMounted(() => {
  verifyEmail()
})

onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }
})
</script>

<style scoped>
.verify-email-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.verify-email-card {
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

.email-display {
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

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
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

.btn-primary:hover {
  background: #5a6fd8;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

/* Responsive */
@media (max-width: 768px) {
  .verify-email-container {
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
  
  .btn {
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
  
  .countdown {
    font-size: 0.8rem;
  }
  
  .btn {
    padding: 10px 16px;
    font-size: 13px;
  }
}
</style>
