<template>
  <nav class="navbar">
    <div class="navbar-container">
      <!-- Logo à gauche -->
      <div class="navbar-logo">
        <button @click="goToHome" class="logo-link">
          <h2>LinkMe</h2>
        </button>
      </div>

      <!-- Barre de recherche au centre -->
      <div class="navbar-search">
        <div class="search-container">
          <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          <input 
            type="text" 
            placeholder="Rechercher..." 
            v-model="searchQuery"
            class="search-input"
            @input="handleSearch"
          />
          <div v-if="showResults" class="search-results">
            <div 
              v-for="u in results"
              :key="u.id"
              class="search-item"
              @click="goToUser(u)"
            >
              <img :src="u.profilePhoto || defaultAvatar" class="result-avatar" />
              <span class="result-name">{{ u.username }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions à droite -->
      <div class="navbar-actions">
        <!-- Message de bienvenue -->
        <div v-if="auth.user && auth.user.name" class="welcome-message">
          <svg class="welcome-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
            <line x1="9" y1="9" x2="9.01" y2="9"></line>
            <line x1="15" y1="9" x2="15.01" y2="9"></line>
          </svg>
          <span class="welcome-text">Bienvenue {{ auth.user.name }}</span>
        </div>

        <!-- Icône notifications -->
        <div class="notification-container" ref="notificationContainer">
          <button class="action-btn notification-btn" @click="toggleNotificationMenu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <!-- Badge de notification -->
            <div v-if="notificationsStore.unreadCount > 0" class="notification-badge">
              {{ notificationsStore.unreadCount > 99 ? '99+' : notificationsStore.unreadCount }}
            </div>
          </button>
          
          <NotificationDropdown 
            :is-visible="isNotificationMenuVisible"
            @close="closeNotificationMenu"
          />
        </div>

        <!-- Icône + -->
        <button class="action-btn add-btn" @click="handleAdd">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>

        <!-- Icône profil utilisateur avec menu déroulant -->
        <div class="profile-container" ref="profileContainer">
          <button class="action-btn profile-btn" @click="toggleProfileMenu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </button>
          
          <UserDropdown 
            :is-visible="isProfileMenuVisible"
            @close="closeProfileMenu"
          />
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { usePostsStore } from '../stores/posts'
import { useNotificationsStore } from '../stores/notifications'
import UserDropdown from './UserDropdown.vue'
import NotificationDropdown from './NotificationDropdown.vue'
import { searchUsers } from '../api'

const searchQuery = ref('')
const results = ref([])
const showResults = ref(false)
const defaultAvatar = 'https://via.placeholder.com/32x32?text=Avatar'
const router = useRouter()
const auth = useAuthStore()
const postsStore = usePostsStore()
const notificationsStore = useNotificationsStore()
const profileContainer = ref(null)
const notificationContainer = ref(null)
const isProfileMenuVisible = ref(false)
const isNotificationMenuVisible = ref(false)

const emit = defineEmits(['openPostModal'])

const handleAdd = () => {
  emit('openPostModal')
}

const goToHome = async () => {
  // Naviguer vers la page Home
  router.push('/')
  
  // Attendre un peu pour que la navigation soit terminée
  await new Promise(resolve => setTimeout(resolve, 100))
  
  // Recharger les posts
  if (auth.isLoggedIn) {
    await postsStore.fetchPosts()
  }
}

const toggleProfileMenu = () => {
  isProfileMenuVisible.value = !isProfileMenuVisible.value
}

const closeProfileMenu = () => {
  isProfileMenuVisible.value = false
}

const toggleNotificationMenu = () => {
  isNotificationMenuVisible.value = !isNotificationMenuVisible.value
  if (isNotificationMenuVisible.value) {
    // Charger les notifications quand on ouvre le menu
    notificationsStore.fetchNotifications()
  }
}

const closeNotificationMenu = () => {
  isNotificationMenuVisible.value = false
  // Marquer toutes les notifications comme lues quand on ferme le menu
  if (notificationsStore.unreadCount > 0) {
    notificationsStore.markAllAsRead()
  }
}

// Fermer le menu quand on clique à l'extérieur
const handleClickOutside = (event) => {
  if (profileContainer.value && !profileContainer.value.contains(event.target)) {
    closeProfileMenu()
  }
  if (notificationContainer.value && !notificationContainer.value.contains(event.target)) {
    closeNotificationMenu()
  }
  if (!event.target.closest('.search-container')) {
    showResults.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  
  // Charger le compteur de notifications non lues
  if (auth.isLoggedIn) {
    notificationsStore.fetchUnreadCount()
    
    // Polling pour mettre à jour le compteur toutes les 30 secondes
    const pollInterval = setInterval(() => {
      if (auth.isLoggedIn) {
        notificationsStore.fetchUnreadCount()
      }
    }, 30000)
    
    // Nettoyer l'intervalle quand le composant est démonté
    onUnmounted(() => {
      clearInterval(pollInterval)
    })
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const handleSearch = async () => {
  const q = searchQuery.value.trim()
  if (!q) {
    results.value = []
    showResults.value = false
    return
  }
  try {
    const { data } = await searchUsers(q)
    results.value = data
    showResults.value = data.length > 0
  } catch (e) {
    results.value = []
    showResults.value = false
  }
}

const goToUser = (u) => {
  showResults.value = false
  searchQuery.value = ''
  router.push(`/u/${encodeURIComponent(u.username)}`)
}
</script>

<style scoped>
.navbar {
  background-color: #fff;
  border-bottom: 1px solid #e1e5e9;
  padding: 0 20px;
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.navbar-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1200px;
  margin: 0 auto;
  height: 60px;
}

.navbar-logo h2 {
  margin: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.5px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  animation: logoGlow 3s ease-in-out infinite alternate;
}

@keyframes logoGlow {
  0% {
    filter: brightness(1);
  }
  100% {
    filter: brightness(1.1);
  }
}

.logo-link {
  background: none;
  border: none;
  text-decoration: none;
  color: inherit;
  transition: all 0.3s ease;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 8px;
  font-family: inherit;
  position: relative;
  overflow: hidden;
}

.logo-link:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.logo-link:hover h2 {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.logo-link h2 {
  margin: 0;
  transition: all 0.3s ease;
}

.logo-link:active {
  transform: translateY(0);
  box-shadow: 0 2px 6px rgba(102, 126, 234, 0.2);
}

.navbar-search {
  flex: 1;
  max-width: 400px;
  margin: 0 40px;
}

.search-container {
  position: relative;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #8e8e8e;
}

.search-input {
  width: 100%;
  padding: 10px 12px 10px 40px;
  border: 1px solid #dbdbdb;
  border-radius: 8px;
  background-color: #fafafa;
  font-size: 14px;
  outline: none;
  transition: all 0.2s ease;
}

.search-input:focus {
  background-color: #fff;
  border-color: #8e8e8e;
}

.search-input::placeholder {
  color: #8e8e8e;
}

.search-results {
  position: absolute;
  top: 110%;
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  z-index: 1500;
}

.search-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  cursor: pointer;
}

.search-item:hover {
  background: #f5f5f5;
}

.result-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
}

.result-name { color: #333; font-size: 14px; }

.navbar-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s ease;
}

.action-btn:hover {
  background-color: #f5f5f5;
}

.add-btn {
  color: #333;
}

.profile-btn {
  color: #333;
}

.profile-container {
  position: relative;
}

.notification-container {
  position: relative;
}

.notification-btn {
  position: relative;
  color: #333;
}

.notification-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  background-color: #ff3040;
  color: white;
  border-radius: 50%;
  min-width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 600;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Message de bienvenue */
.welcome-message {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background-color: #f0f9f0;
  border: 1px solid #4caf50;
  border-radius: 20px;
  margin-right: 8px;
}

.welcome-icon {
  color: #4caf50;
  flex-shrink: 0;
}

.welcome-text {
  color: #2e7d32;
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
}

/* Responsive */
@media (max-width: 1024px) {
  .navbar-search {
    max-width: 300px;
    margin: 0 20px;
  }
}

@media (max-width: 768px) {
  .navbar {
    padding: 0 10px;
  }
  
  .navbar-container {
    height: 50px;
  }
  
  .navbar-search {
    margin: 0 10px;
    max-width: 200px;
  }
  
  .search-input {
    padding: 8px 10px 8px 35px;
    font-size: 13px;
  }
  
  .search-icon {
    left: 10px;
    width: 14px;
    height: 14px;
  }
  
  .navbar-logo h2 {
    font-size: 20px;
  }
  
  .logo-link {
    padding: 4px 8px;
  }
  
  .navbar-actions {
    gap: 8px;
  }
  
  .action-btn {
    padding: 6px;
  }
  
  .action-btn svg {
    width: 18px;
    height: 18px;
  }
  
  .welcome-message {
    padding: 4px 8px;
    margin-right: 4px;
  }
  
  .welcome-text {
    font-size: 11px;
  }
  
  .welcome-icon {
    width: 12px;
    height: 12px;
  }
  
  .notification-badge {
    min-width: 16px;
    height: 16px;
    font-size: 10px;
  }
}

@media (max-width: 640px) {
  .navbar-search {
    display: none; /* Masquer la recherche sur petits écrans */
  }
  
  .welcome-message {
    display: none; /* Masquer le message de bienvenue */
  }
  
  .navbar-actions {
    gap: 6px;
  }
  
  .action-btn {
    padding: 5px;
  }
  
  .action-btn svg {
    width: 16px;
    height: 16px;
  }
}

@media (max-width: 480px) {
  .navbar {
    padding: 0 8px;
  }
  
  .navbar-container {
    height: 45px;
  }
  
  .navbar-logo h2 {
    font-size: 18px;
  }
  
  .logo-link {
    padding: 3px 6px;
  }
  
  .navbar-actions {
    gap: 4px;
  }
  
  .action-btn {
    padding: 4px;
  }
  
  .action-btn svg {
    width: 14px;
    height: 14px;
  }
  
  .notification-badge {
    min-width: 14px;
    height: 14px;
    font-size: 9px;
  }
}
</style>
