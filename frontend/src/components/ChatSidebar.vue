<template>
  <div class="chat-sidebar">
    <div class="chat-header">
      <h3>Private Chat</h3>
    </div>
    
    <div class="conversations-list">
      <!-- Message si aucune conversation -->
      <div v-if="conversations.length === 0" class="no-conversations">
        <p>No conversations yet</p>
        <p class="subtitle">Start chatting with your mutual followers!</p>
      </div>
      
      <!-- Liste des conversations -->
      <div 
        v-for="conversation in conversations" 
        :key="conversation.id" 
        class="conversation-item"
        @click="openConversation(conversation)"
      >
        <div class="user-info">
          <div class="avatar-container">
            <img 
              :src="conversation.otherUser.profilePhoto || defaultAvatar" 
              :alt="conversation.otherUser.name"
              class="user-avatar"
            />
          </div>
          <div class="conversation-details">
            <span class="username">{{ conversation.otherUser.name }}</span>
            <span class="last-message">{{ conversation.lastMessage.preview }}</span>
          </div>
        </div>
        
        <div class="conversation-meta">
          <div class="status-section">
            <div 
              class="status-indicator"
              :class="{ 'online': conversation.otherUser.isOnline, 'offline': !conversation.otherUser.isOnline }"
            ></div>
            <span class="last-seen" v-if="!conversation.otherUser.isOnline">
              Last seen: {{ formatLastSeen(conversation.otherUser.lastSeenAt) }}
            </span>
          </div>
          <span v-if="conversation.unreadCount > 0" class="unread-badge">
            {{ conversation.unreadCount > 99 ? '99+' : conversation.unreadCount }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, defineExpose } from 'vue'
import { getChatConversations } from '../api'

const conversations = ref([])
const defaultAvatar = 'https://via.placeholder.com/40x40?text=Avatar'

const formatLastSeen = (lastSeenAt) => {
  if (!lastSeenAt) return 'Never'
  
  const now = new Date()
  const lastSeen = new Date(lastSeenAt)
  const diffInSeconds = Math.floor((now - lastSeen) / 1000)
  
  if (diffInSeconds < 60) {
    return 'Just now'
  }
  
  const diffInMinutes = Math.floor(diffInSeconds / 60)
  if (diffInMinutes < 60) {
    return `${diffInMinutes}m ago`
  }
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) {
    return `${diffInHours}h ago`
  }
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 7) {
    return `${diffInDays}d ago`
  }
  
  const diffInWeeks = Math.floor(diffInDays / 7)
  if (diffInWeeks < 4) {
    return `${diffInWeeks}w ago`
  }
  
  const diffInMonths = Math.floor(diffInDays / 30)
  if (diffInMonths < 12) {
    return `${diffInMonths}mo ago`
  }
  
  const diffInYears = Math.floor(diffInDays / 365)
  return `${diffInYears}y ago`
}

const loadConversations = async () => {
  try {
    const response = await getChatConversations()
    conversations.value = response.data
    console.log('Conversations loaded:', conversations.value)
  } catch (error) {
    console.error('Error loading conversations:', error)
  }
}

const openConversation = (conversation) => {
  // Émettre un événement pour ouvrir la conversation
  // Ce sera géré par le composant parent
  emit('open-conversation', conversation)
}

// Exposer la fonction pour qu'elle puisse être appelée depuis l'extérieur
defineExpose({
  loadConversations
})

const emit = defineEmits(['open-conversation'])

onMounted(() => {
  console.log('ChatSidebar mounted')
  loadConversations()
  
  // Recharger les conversations toutes les 30 secondes pour mettre à jour les statuts
  const refreshInterval = setInterval(() => {
    loadConversations()
  }, 30000)
  
  // Nettoyer l'intervalle quand le composant est démonté
  onUnmounted(() => {
    clearInterval(refreshInterval)
  })
})
</script>

<style scoped>
.chat-sidebar {
  width: 350px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
  height: fit-content;
  position: sticky;
  top: 80px;
  max-height: calc(100vh - 100px);
  overflow-y: auto;
}

.chat-header {
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e1e5e9;
}

.chat-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #333;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.conversations-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.no-conversations {
  text-align: center;
  padding: 40px 20px;
  color: #666;
}

.no-conversations p {
  margin: 0;
  font-size: 14px;
}

.no-conversations .subtitle {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
}

.conversation-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.conversation-item:hover {
  background-color: #f8f9fa;
  border-color: #e9ecef;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.avatar-container {
  flex-shrink: 0;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e1e5e9;
}

.status-section {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.status-indicator {
  width: 15px;
  height: 15px;
  border-radius: 50%;
  border: 2px solid white;
}

.status-indicator.online {
  background-color: #28a745;
}

.status-indicator.offline {
  background-color: #dc3545;
}

.last-seen {
  font-size: 10px;
  color: #6b6b6b;
  white-space: nowrap;
  text-align: right;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conversation-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  flex: 1;
}

.username {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.last-message {
  font-size: 12px;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conversation-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  flex-shrink: 0;
}

.unread-badge {
  background-color: #007bff;
  color: white;
  border-radius: 10px;
  min-width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 600;
  padding: 0 6px;
}

/* Responsive */
@media (max-width: 1024px) {
  .chat-sidebar {
    display: none;
  }
}

/* Scrollbar styling */
.chat-sidebar::-webkit-scrollbar {
  width: 4px;
}

.chat-sidebar::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 2px;
}

.chat-sidebar::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.chat-sidebar::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
