<template>
  <div class="post-card">
    <!-- Header du post -->
    <div class="post-header">
      <div class="post-author">
        <img 
          :src="post.author.profilePhoto || defaultAvatar" 
          :alt="post.author.name"
          class="author-avatar"
        />
        <div class="author-info">
          <span class="author-name">{{ post.author.name }}</span>
          <span class="post-time">{{ formatTime(post.createdAt) }}</span>
        </div>
      </div>
      
      <!-- Menu des options (seulement pour les posts de l'utilisateur connecté) -->
      <div v-if="isCurrentUserPost" class="post-options" ref="optionsContainer">
        <button class="options-btn" @click="toggleOptionsMenu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="1"></circle>
            <circle cx="12" cy="5" r="1"></circle>
            <circle cx="12" cy="19" r="1"></circle>
          </svg>
        </button>
        
        <div class="options-menu" v-if="isOptionsMenuVisible" @click.stop>
          <button class="option-item edit-option" @click="handleEditPost">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Edit post
          </button>
          <button class="option-item delete-option" @click="handleDeletePost">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3,6 5,6 21,6"></polyline>
              <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
            </svg>
            Delete post
          </button>
        </div>
      </div>
    </div>

    <!-- Contenu du post -->
    <div class="post-content">
      <p class="post-text">{{ post.content }}</p>
      <img 
        v-if="post.media" 
        :src="post.media" 
        :alt="post.content"
        class="post-image"
      />
    </div>

    <!-- Actions du post -->
    <div class="post-actions">
      <button 
        class="action-btn like-btn" 
        :class="{ liked: isLiked }"
        @click="handleToggleLike"
        @mouseenter="showLikesTooltip"
        @mouseleave="hideTooltip"
        :disabled="isLiking"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
        <span class="likes-count">{{ post.likesCount }}</span>
      </button>
      
      <button 
        class="action-btn comment-btn" 
        @click="toggleCommentsModal"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span class="comments-count">{{ post.commentsCount }}</span>
      </button>
    </div>

    <!-- Modale des commentaires -->
    <CommentsModal 
      :is-visible="isCommentsModalVisible"
      :post="post"
      @close="closeCommentsModal"
      @comment-added="handleCommentAdded"
    />
    
    <!-- Modale d'édition de post -->
    <EditPostModal 
      :is-visible="isEditModalVisible"
      :post="post"
      @close="closeEditModal"
      @post-updated="handlePostUpdated"
    />
    
    <!-- Tooltip pour les likes -->
    <FollowersTooltip
      :is-visible="tooltip.isVisible"
      :users="tooltip.users"
      :total-count="tooltip.totalCount"
      :title="tooltip.title"
      :position="tooltip.position"
      @close="hideTooltip"
      @mouseenter="cancelHideTooltip"
      @mouseleave="hideTooltip"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePostsStore } from '../stores/posts'
import { useAuthStore } from '../stores/auth'
import { getLikeStatus, getLikesUsers } from '../api'
import CommentsModal from './CommentsModal.vue'
import EditPostModal from './EditPostModal.vue'
import FollowersTooltip from './FollowersTooltip.vue'

const props = defineProps({
  post: {
    type: Object,
    required: true
  }
})

const postsStore = usePostsStore()
const authStore = useAuthStore()

const isLiked = ref(false)
const isLiking = ref(false)
const isOptionsMenuVisible = ref(false)
const isCommentsModalVisible = ref(false)
const isEditModalVisible = ref(false)
const optionsContainer = ref(null)
const defaultAvatar = 'https://via.placeholder.com/40x40?text=Avatar'

// Tooltip pour les likes
const tooltip = ref({
  isVisible: false,
  users: [],
  totalCount: 0,
  title: '',
  position: { x: 0, y: 0 }
})

let tooltipTimeout = null
let isLoadingTooltip = false

// Vérifier si le post appartient à l'utilisateur connecté
const isCurrentUserPost = computed(() => {
  return authStore.user && props.post.author && authStore.user.id === props.post.author.id
})

const formatTime = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInMinutes = Math.floor((now - date) / (1000 * 60))
  
  if (diffInMinutes < 1) return 'Just now'
  if (diffInMinutes < 60) return `${diffInMinutes} minutes ago`
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 7) return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`
  
  return date.toLocaleDateString()
}

const handleToggleLike = async () => {
  if (isLiking.value) return
  
  isLiking.value = true
  try {
    console.log('Toggling like for post:', props.post.id)
    const result = await postsStore.togglePostLike(props.post.id)
    console.log('Like result:', result)
    isLiked.value = result.liked
  } catch (error) {
    console.error('Error toggling like:', error)
    console.error('Error details:', error.response?.data)
  } finally {
    isLiking.value = false
  }
}

const toggleOptionsMenu = () => {
  isOptionsMenuVisible.value = !isOptionsMenuVisible.value
}

const closeOptionsMenu = () => {
  isOptionsMenuVisible.value = false
}

const handleEditPost = () => {
  isEditModalVisible.value = true
  closeOptionsMenu()
}

const handleDeletePost = async () => {
  if (confirm('Are you sure you want to delete this post?')) {
    try {
      console.log('Deleting post:', props.post.id)
      await postsStore.removePost(props.post.id)
      console.log('Post deleted successfully')
      closeOptionsMenu()
    } catch (error) {
      console.error('Error deleting post:', error)
      console.error('Error details:', error.response?.data)
    }
  }
}

const toggleCommentsModal = () => {
  isCommentsModalVisible.value = !isCommentsModalVisible.value
}

const closeCommentsModal = () => {
  isCommentsModalVisible.value = false
}

const handleCommentAdded = () => {
  // Le compteur de commentaires sera mis à jour automatiquement par le store
}

const closeEditModal = () => {
  isEditModalVisible.value = false
}

const handlePostUpdated = (updatedPost) => {
  // Le post sera mis à jour automatiquement par le store
  closeEditModal()
}

// Fonctions pour le tooltip des likes
const showLikesTooltip = async (event) => {
  // Annuler le timeout de masquage s'il existe
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
    tooltipTimeout = null
  }
  
  // Éviter les appels multiples
  if (isLoadingTooltip) {
    return
  }
  
  console.log('showLikesTooltip called for post:', props.post.id)
  
  try {
    isLoadingTooltip = true
    console.log('Fetching likes for post:', props.post.id)
    const response = await getLikesUsers(props.post.id)
    console.log('Likes response:', response.data)
    const { users, total } = response.data
    
    const rect = event.target.getBoundingClientRect()
    const tooltipHeight = 210 // Hauteur approximative du tooltip
    const viewportHeight = window.innerHeight
    
     // Positionner plus haut pour le tooltip des likes
     let y = rect.top - tooltipHeight - 40 // Plus d'espace au-dessus
     
     // Si pas assez de place au-dessus, positionner en dessous
     if (y < 10) {
       y = rect.bottom + 20
     }
     
     // S'assurer que le tooltip ne sorte pas en bas
     if (y + tooltipHeight > viewportHeight) {
       y = viewportHeight - tooltipHeight - 10
     }
    
    tooltip.value = {
      isVisible: true,
      users: users,
      totalCount: total,
      title: 'Likes',
      position: {
        x: rect.left,
        y: y
      }
    }
    console.log('Likes tooltip set:', tooltip.value)
  } catch (error) {
    console.error('Error loading likes:', error)
  } finally {
    isLoadingTooltip = false
  }
}

const hideTooltip = () => {
  // Ajouter un délai pour éviter les disparitions rapides
  tooltipTimeout = setTimeout(() => {
    tooltip.value.isVisible = false
  }, 100)
}

const cancelHideTooltip = () => {
  // Annuler le timeout de masquage si on entre sur le tooltip
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
    tooltipTimeout = null
  }
}

// Fermer le menu des options quand on clique à l'extérieur
const handleClickOutside = (event) => {
  if (optionsContainer.value && !optionsContainer.value.contains(event.target)) {
    closeOptionsMenu()
  }
}

const loadLikeStatus = async () => {
  try {
    console.log('Loading like status for post:', props.post.id)
    const response = await getLikeStatus(props.post.id)
    console.log('Like status response:', response.data)
    isLiked.value = response.data.liked
  } catch (error) {
    console.error('Error loading like status:', error)
    console.error('Error details:', error.response?.data)
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  loadLikeStatus()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
  }
})
</script>

<style scoped>
.post-card {
  background: white;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  margin-bottom: 20px;
  overflow: hidden;
}

.post-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid #e1e5e9;
}

.post-author {
  display: flex;
  align-items: center;
  gap: 12px;
}

.author-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.author-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.author-name {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.post-time {
  color: #666;
  font-size: 12px;
}

.post-options {
  position: relative;
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

.options-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  min-width: 120px;
  overflow: hidden;
}

.option-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: none;
  border: none;
  cursor: pointer;
  color: #333;
  font-size: 14px;
  transition: background-color 0.2s ease;
  text-align: left;
}

.option-item:hover {
  background-color: #f5f5f5;
}

.edit-option {
  color: #007bff;
}

.delete-option {
  color: #dc3545;
}

.post-content {
  padding: 16px;
}

.post-text {
  margin: 0 0 16px 0;
  color: #333;
  line-height: 1.5;
}

.post-image {
  width: 100%;
  max-height: 400px;
  object-fit: cover;
  border-radius: 8px;
}

.post-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-top: 1px solid #e1e5e9;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  font-size: 14px;
  font-weight: 500;
  transition: color 0.2s ease;
}

.action-btn:hover {
  color: #333;
}

.action-btn.liked {
  color: #e91e63;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.likes-count,
.comments-count {
  font-size: 14px;
  font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
  .post-card {
    margin-bottom: 15px;
  }
  
  .post-header {
    padding: 12px;
  }
  
  .user-avatar {
    width: 35px;
    height: 35px;
  }
  
  .user-info h3 {
    font-size: 14px;
  }
  
  .post-time {
    font-size: 11px;
  }
  
  .post-content {
    padding: 12px;
  }
  
  .post-text {
    font-size: 14px;
    line-height: 1.4;
  }
  
  .post-image {
    max-height: 300px;
  }
  
  .post-actions {
    padding: 12px;
  }
  
  .action-btn {
    padding: 8px;
  }
  
  .action-btn svg {
    width: 18px;
    height: 18px;
  }
  
  .likes-count,
  .comments-count {
    font-size: 13px;
  }
}

@media (max-width: 640px) {
  .post-card {
    margin-bottom: 12px;
  }
  
  .post-header {
    padding: 10px;
  }
  
  .user-avatar {
    width: 32px;
    height: 32px;
  }
  
  .user-info h3 {
    font-size: 13px;
  }
  
  .post-time {
    font-size: 10px;
  }
  
  .post-content {
    padding: 10px;
  }
  
  .post-text {
    font-size: 13px;
  }
  
  .post-image {
    max-height: 250px;
  }
  
  .post-actions {
    padding: 10px;
  }
  
  .action-btn {
    padding: 6px;
  }
  
  .action-btn svg {
    width: 16px;
    height: 16px;
  }
  
  .likes-count,
  .comments-count {
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .post-card {
    margin-bottom: 10px;
  }
  
  .post-header {
    padding: 8px;
  }
  
  .user-avatar {
    width: 30px;
    height: 30px;
  }
  
  .user-info h3 {
    font-size: 12px;
  }
  
  .post-time {
    font-size: 9px;
  }
  
  .post-content {
    padding: 8px;
  }
  
  .post-text {
    font-size: 12px;
  }
  
  .post-image {
    max-height: 200px;
  }
  
  .post-actions {
    padding: 8px;
  }
  
  .action-btn {
    padding: 5px;
  }
  
  .action-btn svg {
    width: 14px;
    height: 14px;
  }
  
  .likes-count,
  .comments-count {
    font-size: 11px;
  }
  
  .options-menu {
    min-width: 100px;
  }
  
  .option-item {
    padding: 10px 12px;
    font-size: 13px;
  }
}
</style>
