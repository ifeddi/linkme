<template>
  <div v-if="isVisible" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Edit Post</h3>
        <button class="close-btn" @click="closeModal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      
      <div class="modal-body">
        <textarea 
          v-model="postContent"
          placeholder="What's in your mind?"
          class="post-textarea"
          ref="textareaRef"
          @input="autoResize"
        ></textarea>
        
        <!-- Aperçu de l'image actuelle (seulement si pas de nouvelles images et pas de suppression) -->
        <div v-if="currentImage && selectedFiles.length === 0" class="current-image-section">
          <h4>Current Image:</h4>
          <div class="current-image-container">
            <img 
              :src="currentImage" 
              :alt="postContent"
              class="current-image"
            />
            <button 
              @click="removeCurrentImage" 
              class="remove-current-image-btn"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Message de suppression d'image -->
        <div v-if="selectedFiles.length > 0 && selectedFiles[0].isRemoval" class="removal-message">
          <div class="removal-indicator">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3,6 5,6 21,6"></polyline>
              <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
            </svg>
            <span>Image will be removed</span>
          </div>
        </div>
        
        <!-- Aperçu des nouveaux médias sélectionnés -->
        <div v-if="selectedFiles.length > 0 && !selectedFiles[0].isRemoval" class="media-preview">
          <h4>New Image:</h4>
          <div 
            v-for="(file, index) in selectedFiles" 
            :key="index" 
            class="media-item"
          >
            <div class="media-preview-container">
              <img 
                v-if="file.type.startsWith('image/')" 
                :src="file.preview" 
                :alt="file.name"
                class="media-image"
              />
              <video 
                v-else-if="file.type.startsWith('video/')" 
                :src="file.preview" 
                class="media-video"
                controls
              ></video>
            </div>
            <button 
              @click="removeFile(index)" 
              class="remove-file-btn"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="attachment-btn" @click="handleAttachment">
          <svg class="camera-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
            <circle cx="12" cy="13" r="4"></circle>
          </svg>
          {{ currentImage ? 'Change Image' : 'Add Image' }}
        </button>
        
        <!-- Input file caché -->
        <input 
          ref="fileInput"
          type="file" 
          accept="image/*,video/*" 
          multiple
          @change="handleFileSelect"
          style="display: none"
        />
        
        <button 
          class="update-btn" 
          @click="handleUpdate"
          :disabled="!postContent.trim() || isUpdating"
        >
          <span v-if="isUpdating">Updating...</span>
          <span v-else>Update Post</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, watch, computed } from 'vue'
import { usePostsStore } from '../stores/posts'

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

const emit = defineEmits(['close', 'post-updated'])

const postsStore = usePostsStore()
const postContent = ref('')
const textareaRef = ref(null)
const fileInput = ref(null)
const selectedFiles = ref([])
const isUpdating = ref(false)

// Image actuelle du post
const currentImage = computed(() => {
  return props.post?.media || null
})

const closeModal = () => {
  // Nettoyer les fichiers sélectionnés
  selectedFiles.value.forEach(file => {
    if (file.preview) {
      URL.revokeObjectURL(file.preview)
    }
  })
  selectedFiles.value = []
  postContent.value = ''
  emit('close')
}

const handleAttachment = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  
  // Nettoyer les fichiers précédents
  selectedFiles.value.forEach(file => {
    if (file.preview) {
      URL.revokeObjectURL(file.preview)
    }
  })
  selectedFiles.value = []
  
  files.forEach(file => {
    // Vérifier le type de fichier
    if (file.type.startsWith('image/') || file.type.startsWith('video/')) {
      // Vérifier la taille (max 10MB)
      if (file.size > 10 * 1024 * 1024) {
        alert('Le fichier est trop volumineux. Taille maximum: 10MB')
        return
      }
      
      const fileWithPreview = {
        file: file,
        name: file.name,
        type: file.type,
        size: file.size,
        preview: URL.createObjectURL(file)
      }
      
      selectedFiles.value.push(fileWithPreview)
    } else {
      alert('Type de fichier non supporté. Seules les images et vidéos sont acceptées.')
    }
  })
  
  // Réinitialiser l'input
  event.target.value = ''
}

const removeFile = (index) => {
  const fileToRemove = selectedFiles.value[index]
  if (fileToRemove.preview) {
    URL.revokeObjectURL(fileToRemove.preview)
  }
  selectedFiles.value.splice(index, 1)
  
  // Si on supprime le fichier de suppression, on revient à l'état initial
  if (fileToRemove.isRemoval) {
    // L'image actuelle sera de nouveau visible
  }
}

const removeCurrentImage = () => {
  // Marquer l'image actuelle comme supprimée
  // On utilisera une valeur spéciale pour indiquer la suppression
  selectedFiles.value = [{
    file: null,
    name: 'remove-current',
    type: 'remove',
    size: 0,
    preview: null,
    isRemoval: true
  }]
}

const convertFileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result)
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

const handleUpdate = async () => {
  if (!postContent.value.trim()) return
  
  isUpdating.value = true
  try {
    let mediaUrl = currentImage.value
    
    // Gérer les nouveaux fichiers ou la suppression
    if (selectedFiles.value.length > 0) {
      const removalFile = selectedFiles.value.find(f => f.isRemoval)
      if (removalFile) {
        // Supprimer l'image actuelle
        mediaUrl = null
        console.log('Removing current image')
      } else {
        // Utiliser la nouvelle image
        const newFile = selectedFiles.value.find(f => f.file)
        if (newFile) {
          console.log('Converting new image to Base64...', newFile.file)
          mediaUrl = await convertFileToBase64(newFile.file)
          console.log('New image converted to Base64 successfully')
        }
      }
    }
    
    const postData = {
      content: postContent.value.trim(),
      media: mediaUrl
    }
    
    console.log('Updating post:', props.post.id, 'with data:', postData)
    const updatedPost = await postsStore.updatePost(props.post.id, postData)
    console.log('Post updated successfully:', updatedPost)
    
    emit('post-updated', updatedPost)
    
    // Nettoyer les fichiers
    selectedFiles.value.forEach(file => {
      if (file.preview) {
        URL.revokeObjectURL(file.preview)
      }
    })
    selectedFiles.value = []
    postContent.value = ''
    closeModal()
  } catch (error) {
    console.error('Error updating post:', error)
    console.error('Error details:', error.response?.data)
    alert('Erreur lors de la mise à jour du post. Veuillez réessayer.')
  } finally {
    isUpdating.value = false
  }
}

const autoResize = () => {
  nextTick(() => {
    if (textareaRef.value) {
      textareaRef.value.style.height = 'auto'
      textareaRef.value.style.height = textareaRef.value.scrollHeight + 'px'
    }
  })
}

// Initialiser le contenu quand la modale s'ouvre
watch(() => props.isVisible, (newValue) => {
  if (newValue) {
    postContent.value = props.post.content || ''
    nextTick(() => {
      if (textareaRef.value) {
        textareaRef.value.focus()
        autoResize()
      }
    })
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
  min-height: 120px;
  overflow-y: auto;
}

.post-textarea {
  width: 100%;
  min-height: 100px;
  border: none;
  outline: none;
  resize: none;
  font-size: 16px;
  line-height: 1.5;
  font-family: inherit;
  color: #333;
  background: transparent;
}

.post-textarea::placeholder {
  color: #999;
}

/* Styles pour l'image actuelle */
.current-image-section {
  margin-top: 20px;
}

.current-image-section h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: #666;
}

.current-image-container {
  position: relative;
  display: inline-block;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.current-image {
  width: 200px;
  height: 200px;
  object-fit: cover;
  display: block;
}

.remove-current-image-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background-color: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.remove-current-image-btn:hover {
  background-color: rgba(0, 0, 0, 0.9);
}

/* Message de suppression */
.removal-message {
  margin-top: 20px;
}

.removal-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  border-radius: 8px;
  color: #721c24;
  font-size: 14px;
  font-weight: 500;
}

.removal-indicator svg {
  color: #dc3545;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px 20px;
  border-top: 1px solid #e1e5e9;
}

.attachment-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  font-size: 14px;
  font-weight: 500;
  padding: 8px 12px;
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

.attachment-btn:hover {
  background-color: #f5f5f5;
}

.camera-icon {
  color: #666;
}

.update-btn {
  background-color: #28a745;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.update-btn:hover:not(:disabled) {
  background-color: #218838;
}

.update-btn:disabled {
  background-color: #c7c7c7;
  cursor: not-allowed;
}

/* Styles pour les aperçus de médias */
.media-preview {
  margin-top: 16px;
}

.media-preview h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: #666;
}

.media-item {
  position: relative;
  display: inline-block;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-right: 12px;
}

.media-preview-container {
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f5f5f5;
}

.media-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.remove-file-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  background-color: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.remove-file-btn:hover {
  background-color: rgba(0, 0, 0, 0.9);
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
  
  .current-image {
    width: 150px;
    height: 150px;
  }
}
</style>
