<template>
  <div v-if="isVisible" class="chat-modal-overlay" @click="closeModal">
    <div class="chat-modal-content" @click.stop>
      <!-- Header de la conversation -->
      <div class="chat-header">
        <button class="back-btn" @click="closeModal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15,18 9,12 15,6"></polyline>
          </svg>
        </button>
        <div class="user-info">
          <div class="avatar-container">
            <img 
              :src="conversation?.otherUser.profilePhoto || defaultAvatar" 
              :alt="conversation?.otherUser.name"
              class="user-avatar"
            />
            <div 
              class="status-indicator"
              :class="{ 'online': conversation?.otherUser.isOnline, 'offline': !conversation?.otherUser.isOnline }"
            ></div>
          </div>
          <div class="user-details">
            <h3>{{ conversation?.otherUser.name }}</h3>
            <span class="status-text">
              {{ conversation?.otherUser.isOnline ? 'Online' : 'Offline' }}
            </span>
          </div>
        </div>
        <button class="options-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="1"></circle>
            <circle cx="12" cy="5" r="1"></circle>
            <circle cx="12" cy="19" r="1"></circle>
          </svg>
        </button>
      </div>
      
      <!-- Messages -->
      <div class="messages-container" ref="messagesContainer">
        <div v-if="messages.length === 0" class="no-messages">
          <p>No messages yet</p>
          <p class="subtitle">Start the conversation!</p>
        </div>
        
        <div 
          v-for="message in messages" 
          :key="message.id" 
          class="message-item"
          :class="{ 'own-message': message.isOwn, 'other-message': !message.isOwn }"
        >
          <div class="message-bubble">
            <div v-if="message.isSticker" class="sticker-message">
              <span class="sticker">{{ message.stickerCode }}</span>
            </div>
            <div v-else class="text-message">
              {{ message.content }}
            </div>
            <div class="message-time">
              {{ formatTime(message.createdAt) }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- Input area -->
      <div class="chat-input-container">
        <button class="sticker-btn" @click="toggleStickerMenu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
          </svg>
        </button>
        
        <div class="input-wrapper">
          <input 
            v-model="newMessage"
            type="text" 
            placeholder="Type a message..."
            class="message-input"
            @keyup.enter="sendMessage"
            :disabled="isSending"
          />
          <button 
            class="send-btn"
            @click="sendMessage"
            :disabled="!newMessage.trim() || isSending"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22,2 15,22 11,13 2,9 22,2"></polygon>
            </svg>
          </button>
        </div>
      </div>
      
      <!-- Sticker menu -->
      <div v-if="showStickerMenu" class="sticker-menu">
        <div class="sticker-grid">
          <button 
            v-for="sticker in stickers" 
            :key="sticker.code"
            class="sticker-item"
            @click="addSticker(sticker.code)"
          >
            {{ sticker.code }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { getChatMessages, sendChatMessage, getChatStickers } from '../api'

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  },
  conversation: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close'])

const messages = ref([])
const newMessage = ref('')
const isSending = ref(false)
const showStickerMenu = ref(false)
const stickers = ref([])
const messagesContainer = ref(null)
const defaultAvatar = 'https://via.placeholder.com/40x40?text=Avatar'
let eventSource = null

const loadMessages = async () => {
  if (!props.conversation) return
  
  try {
    const response = await getChatMessages(props.conversation.id)
    messages.value = response.data
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Error loading messages:', error)
  }
}

const loadStickers = async () => {
  try {
    const response = await getChatStickers()
    stickers.value = response.data
  } catch (error) {
    console.error('Error loading stickers:', error)
  }
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value || !props.conversation) {
    console.log('Cannot send message:', {
      hasContent: !!newMessage.value.trim(),
      isSending: isSending.value,
      hasConversation: !!props.conversation,
      conversationId: props.conversation?.id
    })
    return
  }
  
  isSending.value = true
  try {
    console.log('Sending message:', {
      conversationId: props.conversation.id,
      content: newMessage.value.trim()
    })
    
    const response = await sendChatMessage(props.conversation.id, {
      content: newMessage.value.trim(),
      isSticker: false
    })
    
    console.log('Message sent successfully:', response.data)
    
    // Ajouter le message à la liste localement
    messages.value.push(response.data)
    newMessage.value = ''
    showStickerMenu.value = false
    
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Error sending message:', error)
    if (error.response) {
      console.error('Response data:', error.response.data)
      console.error('Response status:', error.response.status)
    }
  } finally {
    isSending.value = false
  }
}

const addSticker = (stickerCode) => {
  // Ajouter le sticker au champ de texte au lieu de l'envoyer directement
  console.log('Adding sticker to input:', stickerCode)
  console.log('Current message value:', newMessage.value)
  newMessage.value += stickerCode
  console.log('New message value:', newMessage.value)
  showStickerMenu.value = false
}

const toggleStickerMenu = () => {
  showStickerMenu.value = !showStickerMenu.value
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const setupWebSocket = () => {
  if (!props.conversation) return
  
  // Fermer la connexion existante
  if (eventSource) {
    eventSource.close()
  }
  
  // Désactiver temporairement WebSocket pour éviter les erreurs
  console.log('WebSocket désactivé temporairement - Mercure non disponible')
  
  // TODO: Réactiver quand Mercure sera configuré correctement
  /*
  // Créer une nouvelle connexion EventSource pour Mercure
  const url = new URL('http://localhost:3000/.well-known/mercure')
  url.searchParams.append('topic', `chat/${props.conversation.id}`)
  
  eventSource = new EventSource(url.toString())
  
  eventSource.onmessage = (event) => {
    try {
      const data = JSON.parse(event.data)
      if (data.type === 'message') {
        // Ajouter le nouveau message
        messages.value.push(data.message)
        nextTick(() => {
          scrollToBottom()
        })
      }
    } catch (error) {
      console.error('Error parsing WebSocket message:', error)
    }
  }
  
  eventSource.onerror = (error) => {
    console.error('WebSocket error:', error)
  }
  */
}

const closeModal = () => {
  if (eventSource) {
    eventSource.close()
    eventSource = null
  }
  emit('close')
}

// Watchers
watch(() => props.isVisible, (newValue) => {
  if (newValue && props.conversation) {
    loadMessages()
    setupWebSocket()
  } else if (!newValue) {
    if (eventSource) {
      eventSource.close()
      eventSource = null
    }
  }
})

watch(() => props.conversation, (newValue) => {
  if (newValue && props.isVisible) {
    loadMessages()
    setupWebSocket()
  }
})

onMounted(() => {
  loadStickers()
})

onUnmounted(() => {
  if (eventSource) {
    eventSource.close()
  }
})
</script>

<style scoped>
.chat-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 20px;
}

.chat-modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  height: 600px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-header {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e1e5e9;
  background: #fafafa;
}

.back-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  transition: background-color 0.2s ease;
  margin-right: 12px;
}

.back-btn:hover {
  background-color: #f5f5f5;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.avatar-container {
  position: relative;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e1e5e9;
}

.status-indicator {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
}

.status-indicator.online {
  background-color: #28a745;
}

.status-indicator.offline {
  background-color: #dc3545;
}

.user-details h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.status-text {
  font-size: 12px;
  color: #666;
}

.options-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  transition: background-color 0.2s ease;
}

.options-btn:hover {
  background-color: #f5f5f5;
}

.messages-container {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.no-messages {
  text-align: center;
  padding: 40px 20px;
  color: #666;
}

.no-messages p {
  margin: 0;
  font-size: 14px;
}

.no-messages .subtitle {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
}

.message-item {
  display: flex;
  width: 100%;
}

.message-item.own-message {
  justify-content: flex-end;
}

.message-item.other-message {
  justify-content: flex-start;
}

.message-bubble {
  max-width: 70%;
  padding: 12px 16px;
  border-radius: 18px;
  position: relative;
}

.own-message .message-bubble {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom-right-radius: 4px;
}

.other-message .message-bubble {
  background: #f1f3f4;
  color: #333;
  border-bottom-left-radius: 4px;
}

.sticker-message {
  text-align: center;
}

.sticker {
  font-size: 32px;
  display: block;
}

.text-message {
  line-height: 1.4;
  word-wrap: break-word;
}

.message-time {
  font-size: 11px;
  opacity: 0.7;
  margin-top: 4px;
  text-align: right;
}

.other-message .message-time {
  text-align: left;
}

.chat-input-container {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  border-top: 1px solid #e1e5e9;
  background: #fafafa;
  gap: 12px;
}

.sticker-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  transition: background-color 0.2s ease;
}

.sticker-btn:hover {
  background-color: #f5f5f5;
}

.input-wrapper {
  flex: 1;
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #e1e5e9;
  border-radius: 20px;
  padding: 8px 12px;
  gap: 8px;
}

.message-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  background: transparent;
}

.send-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  transition: opacity 0.2s ease;
}

.send-btn:hover:not(:disabled) {
  opacity: 0.9;
}

.send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.sticker-menu {
  position: absolute;
  bottom: 80px;
  left: 50%;
  transform: translateX(-50%);
  width: 240px;
  background: white;
  border: 1px solid #e1e5e9;
  border-radius: 12px;
  padding: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  max-height: 180px;
  overflow-y: auto;
}

.sticker-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
}

.sticker-item {
  background: none;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  padding: 8px;
  cursor: pointer;
  font-size: 24px;
  transition: background-color 0.2s ease;
}

.sticker-item:hover {
  background-color: #f8f9fa;
}

/* Responsive */
@media (max-width: 768px) {
  .chat-modal-overlay {
    padding: 10px;
  }
  
  .chat-modal-content {
    margin: 0;
    width: 100%;
    max-width: 100%;
    height: 95vh;
    max-height: 95vh;
    border-radius: 8px;
  }
  
  .chat-header {
    padding: 12px 16px;
  }
  
  .user-avatar {
    width: 35px;
    height: 35px;
  }
  
  .user-details h3 {
    font-size: 15px;
  }
  
  .status-text {
    font-size: 11px;
  }
  
  .messages-container {
    padding: 16px;
  }
  
  .message-bubble {
    max-width: 85%;
    padding: 10px 14px;
  }
  
  .chat-input-container {
    padding: 12px 16px;
  }
  
  .message-input {
    font-size: 16px; /* Évite le zoom sur iOS */
  }
  
  .sticker-menu {
    width: 200px;
    max-height: 150px;
  }
  
  .sticker-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 640px) {
  .chat-modal-overlay {
    padding: 5px;
  }
  
  .chat-modal-content {
    height: 98vh;
    max-height: 98vh;
    border-radius: 6px;
  }
  
  .chat-header {
    padding: 10px 12px;
  }
  
  .user-avatar {
    width: 32px;
    height: 32px;
  }
  
  .user-details h3 {
    font-size: 14px;
  }
  
  .status-text {
    font-size: 10px;
  }
  
  .messages-container {
    padding: 12px;
  }
  
  .message-bubble {
    max-width: 90%;
    padding: 8px 12px;
  }
  
  .chat-input-container {
    padding: 10px 12px;
  }
  
  .sticker-menu {
    width: 180px;
    max-height: 120px;
  }
  
  .sticker-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 480px) {
  .chat-modal-overlay {
    padding: 0;
  }
  
  .chat-modal-content {
    height: 100vh;
    max-height: 100vh;
    border-radius: 0;
    margin: 0;
  }
  
  .chat-header {
    padding: 8px 10px;
  }
  
  .user-avatar {
    width: 30px;
    height: 30px;
  }
  
  .user-details h3 {
    font-size: 13px;
  }
  
  .status-text {
    font-size: 9px;
  }
  
  .messages-container {
    padding: 10px;
  }
  
  .message-bubble {
    max-width: 95%;
    padding: 6px 10px;
  }
  
  .chat-input-container {
    padding: 8px 10px;
  }
  
  .input-wrapper {
    padding: 6px 10px;
  }
  
  .message-input {
    font-size: 15px;
  }
  
  .sticker-menu {
    width: 160px;
    max-height: 100px;
  }
  
  .sticker-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
  }
  
  .sticker-item {
    padding: 6px;
    font-size: 20px;
  }
}
</style>
