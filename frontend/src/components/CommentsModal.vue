<template>
  <div v-if="isVisible" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Comments</h3>
        <button class="close-btn" @click="closeModal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      
      <div class="modal-body">
        <!-- Liste des commentaires -->
        <div class="comments-list" v-if="comments.length > 0">
          <div 
            v-for="comment in comments" 
            :key="comment.id" 
            class="comment-item"
          >
            <img 
              :src="comment.author.profilePhoto || defaultAvatar" 
              :alt="comment.author.name"
              class="comment-avatar"
            />
            <div class="comment-content">
              <div class="comment-header">
                <span class="comment-author">{{ comment.author.name }}</span>
                <span class="comment-time">{{ formatTime(comment.createdAt) }}</span>
              </div>
              <p class="comment-text">{{ comment.content }}</p>
            </div>
            <button 
              v-if="canDeleteComment(comment)"
              class="delete-comment-btn"
              @click="handleDeleteComment(comment.id)"
              :disabled="isDeleting"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3,6 5,6 21,6"></polyline>
                <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Message si aucun commentaire -->
        <div v-else class="no-comments">
          <p>No comments yet. Be the first to comment!</p>
        </div>
      </div>
      
      <div class="modal-footer">
        <div class="comment-input-container">
          <input 
            v-model="newComment"
            type="text" 
            placeholder="Your Comment!"
            class="comment-input"
            @keyup.enter="handleAddComment"
            :disabled="isAddingComment"
          />
          <button 
            class="add-comment-btn"
            @click="handleAddComment"
            :disabled="!newComment.trim() || isAddingComment"
          >
            <span v-if="isAddingComment">Adding...</span>
            <span v-else>Add Comment</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { usePostsStore } from '../stores/posts'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  },
  post: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'comment-added'])

const postsStore = usePostsStore()
const authStore = useAuthStore()

const comments = ref([])
const newComment = ref('')
const isLoading = ref(false)
const isAddingComment = ref(false)
const isDeleting = ref(false)
const defaultAvatar = 'https://via.placeholder.com/32x32?text=Avatar'

const canDeleteComment = (comment) => {
  return authStore.user && comment.author.id === authStore.user.id
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInMinutes = Math.floor((now - date) / (1000 * 60))
  
  if (diffInMinutes < 1) return 'Just now'
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) return `${diffInHours}h ago`
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 7) return `${diffInDays}d ago`
  
  return date.toLocaleDateString()
}

const loadComments = async () => {
  if (!props.post) return
  
  isLoading.value = true
  try {
    console.log('Loading comments for post:', props.post.id)
    comments.value = await postsStore.getPostComments(props.post.id)
    console.log('Comments loaded:', comments.value)
  } catch (error) {
    console.error('Error loading comments:', error)
    console.error('Error details:', error.response?.data)
  } finally {
    isLoading.value = false
  }
}

const handleAddComment = async () => {
  if (!newComment.value.trim() || isAddingComment.value) return
  
  isAddingComment.value = true
  try {
    console.log('Adding comment for post:', props.post.id, 'Content:', newComment.value.trim())
    const comment = await postsStore.addComment(props.post.id, newComment.value.trim())
    console.log('Comment added:', comment)
    comments.value.push(comment)
    newComment.value = ''
    emit('comment-added')
  } catch (error) {
    console.error('Error adding comment:', error)
    console.error('Error details:', error.response?.data)
  } finally {
    isAddingComment.value = false
  }
}

const handleDeleteComment = async (commentId) => {
  if (isDeleting.value) return
  
  isDeleting.value = true
  try {
    await postsStore.removeComment(commentId, props.post.id)
    comments.value = comments.value.filter(comment => comment.id !== commentId)
  } catch (error) {
    console.error('Error deleting comment:', error)
  } finally {
    isDeleting.value = false
  }
}

const closeModal = () => {
  emit('close')
}

// Charger les commentaires quand la modale s'ouvre
watch(() => props.isVisible, (newValue) => {
  if (newValue) {
    loadComments()
  }
})

onMounted(() => {
  if (props.isVisible) {
    loadComments()
  }
})
</script>

<style scoped>
.modal-overlay {
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

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 80vh;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 16px;
  border-bottom: 1px solid #e1e5e9;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  transition: background-color 0.2s ease;
}

.close-btn:hover {
  background-color: #f5f5f5;
}

.modal-body {
  flex: 1;
  padding: 20px 24px;
  overflow-y: auto;
  max-height: 400px;
}

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.comment-item {
  display: flex;
  gap: 12px;
  position: relative;
}

.comment-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.comment-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.comment-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.comment-author {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.comment-time {
  color: #666;
  font-size: 12px;
}

.comment-text {
  margin: 0;
  color: #333;
  font-size: 14px;
  line-height: 1.4;
}

.delete-comment-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #dc3545;
  transition: background-color 0.2s ease;
  opacity: 0.7;
}

.comment-item:hover .delete-comment-btn {
  opacity: 1;
}

.delete-comment-btn:hover {
  background-color: #f8d7da;
}

.delete-comment-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.no-comments {
  text-align: center;
  padding: 40px 20px;
  color: #666;
}

.modal-footer {
  padding: 16px 24px 20px;
  border-top: 1px solid #e1e5e9;
}

.comment-input-container {
  display: flex;
  gap: 12px;
  align-items: center;
}

.comment-input {
  flex: 1;
  padding: 12px;
  border: 1px solid #dbdbdb;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease;
}

.comment-input:focus {
  border-color: #0095f6;
}

.comment-input:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.add-comment-btn {
  background: #333;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
  white-space: nowrap;
}

.add-comment-btn:hover:not(:disabled) {
  background: #555;
}

.add-comment-btn:disabled {
  background: #c7c7c7;
  cursor: not-allowed;
}

/* Animation d'ouverture */
.modal-overlay {
  animation: fadeIn 0.2s ease-out;
}

.modal-content {
  animation: slideUp 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .modal-content {
    margin: 0 16px;
    max-height: 90vh;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 20px;
    padding-right: 20px;
  }
  
  .comment-input-container {
    flex-direction: column;
    gap: 8px;
  }
  
  .add-comment-btn {
    width: 100%;
  }
}
</style>
