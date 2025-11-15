<template>
  <div class="notification-dropdown" v-if="isVisible" @click.stop>
    <div class="dropdown-content">
      <div class="dropdown-header">
        <h3>Notifications</h3>
        <button 
          v-if="notificationsStore.notifications.length > 0"
          class="mark-all-read-btn"
          @click="handleMarkAllAsRead"
          :disabled="notificationsStore.isLoading"
        >
          Mark all as read
        </button>
      </div>
      
      <div class="notifications-list">
        <!-- Message si aucune notification -->
        <div v-if="notificationsStore.notifications.length === 0" class="no-notifications">
          <p>No notifications yet</p>
        </div>
        
        <!-- Liste des notifications -->
        <div 
          v-for="notification in notificationsStore.notifications" 
          :key="notification.id" 
          class="notification-item"
          :class="{ unread: !notification.isRead }"
        >
          <div class="notification-avatar">
            <img 
              v-if="notification.actor && notification.actor.profilePhoto"
              :src="notification.actor.profilePhoto" 
              :alt="notification.actor.name"
              class="actor-avatar"
            />
            <div v-else class="default-avatar">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
          </div>
          
          <div class="notification-content">
            <div class="notification-message" v-html="formatNotificationMessage(notification)"></div>
            <div class="notification-time">{{ notification.timeAgo }}</div>
            
            <!-- Boutons pour les demandes de follow -->
            <div v-if="notification.type === 'follow_request'" class="follow-request-actions">
              <button class="accept-btn" @click="handleAcceptFollow(notification)" :disabled="isProcessing">
                Accepter
              </button>
              <button class="reject-btn" @click="handleRejectFollow(notification)" :disabled="isProcessing">
                Refuser
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useNotificationsStore } from '../stores/notifications'
import { acceptFollow, rejectFollow } from '../api'
import { usePostsStore } from '../stores/posts'
import { useFollowingsStore } from '../stores/followings'

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const notificationsStore = useNotificationsStore()
const postsStore = usePostsStore()
const followingsStore = useFollowingsStore()
const isProcessing = ref(false)

const handleMarkAllAsRead = async () => {
  await notificationsStore.markAllAsRead()
}

const handleAcceptFollow = async (notification) => {
  if (isProcessing.value) return
  
  isProcessing.value = true
  try {
    await acceptFollow(notification.actor.id)
    // Supprimer la notification de la liste
    notificationsStore.notifications = notificationsStore.notifications.filter(n => n.id !== notification.id)
    // Mettre à jour le compteur
    notificationsStore.fetchUnreadCount()
    // Recharger les posts pour que l'utilisateur accepté apparaisse dans le feed
    await postsStore.refreshPosts()
    // Recharger la liste des followings pour que l'utilisateur accepté apparaisse dans la sidebar
    await followingsStore.refreshFollowings()
  } catch (error) {
    console.error('Error accepting follow:', error)
  } finally {
    isProcessing.value = false
  }
}

const handleRejectFollow = async (notification) => {
  if (isProcessing.value) return
  
  isProcessing.value = true
  try {
    await rejectFollow(notification.actor.id)
    // Supprimer la notification de la liste
    notificationsStore.notifications = notificationsStore.notifications.filter(n => n.id !== notification.id)
    // Mettre à jour le compteur
    notificationsStore.fetchUnreadCount()
  } catch (error) {
    console.error('Error rejecting follow:', error)
  } finally {
    isProcessing.value = false
  }
}

const formatNotificationMessage = (notification) => {
  let message = notification.message
  
  // Mettre en gras le nom de l'utilisateur
  if (notification.actor) {
    const actorName = notification.actor.name
    message = message.replace(actorName, `<strong>${actorName}</strong>`)
  }
  
  return message
}

onMounted(() => {
  if (props.isVisible) {
    notificationsStore.fetchNotifications()
  }
})
</script>

<style scoped>
.notification-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  border: 1px solid #e1e5e9;
  z-index: 1000;
  width: 400px;
  max-height: 500px;
  overflow: hidden;
}

.dropdown-content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #e1e5e9;
  background: #fafafa;
}

.dropdown-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.mark-all-read-btn {
  background: none;
  border: none;
  color: #0095f6;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.mark-all-read-btn:hover:not(:disabled) {
  background-color: #f0f8ff;
}

.mark-all-read-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.notifications-list {
  flex: 1;
  overflow-y: auto;
  max-height: 400px;
}

.no-notifications {
  text-align: center;
  padding: 40px 20px;
  color: #666;
}

.no-notifications p {
  margin: 0;
  font-size: 14px;
}

.notification-item {
  display: flex;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s ease;
}

.notification-item:hover {
  background-color: #fafafa;
}

.notification-item.unread {
  background-color: #f8f9ff;
  border-left: 3px solid #0095f6;
}

.notification-avatar {
  flex-shrink: 0;
}

.actor-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.default-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: #e1e5e9;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
}

.notification-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.notification-message {
  font-size: 14px;
  line-height: 1.4;
  color: #333;
}

.notification-message :deep(strong) {
  font-weight: 600;
  color: #333;
}

.notification-time {
  font-size: 12px;
  color: #666;
}

.follow-request-actions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.accept-btn,
.reject-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.accept-btn {
  background-color: #0095f6;
  color: white;
}

.accept-btn:hover:not(:disabled) {
  background-color: #0081d6;
}

.reject-btn {
  background-color: #dc3545;
  color: white;
}

.reject-btn:hover:not(:disabled) {
  background-color: #c82333;
}

.accept-btn:disabled,
.reject-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .notification-dropdown {
    width: 320px;
    right: -50px;
  }
  
  .dropdown-header {
    padding: 12px 16px;
  }
  
  .notification-item {
    padding: 12px 16px;
  }
}
</style>
