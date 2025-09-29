<template>
  <div v-if="isVisible" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>What's in your mind ?</h3>
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
          placeholder=""
          class="post-textarea"
          ref="textareaRef"
          @input="autoResize"
        ></textarea>
        
        <!-- Aperçu des médias sélectionnés -->
        <div v-if="selectedFiles.length > 0" class="media-preview">
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
          Photo or Video
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
          class="share-btn" 
          @click="handleShare"
          :disabled="!postContent.trim() && selectedFiles.length === 0"
        >
          Share
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue'

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'share'])

const postContent = ref('')
const textareaRef = ref(null)
const fileInput = ref(null)
const selectedFiles = ref([])

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
}

const convertFileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result)
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

const handleShare = async () => {
  if (postContent.value.trim() || selectedFiles.value.length > 0) {
    try {
      let mediaUrl = null
      
      // Encoder l'image en Base64 si il y en a une
      if (selectedFiles.value.length > 0) {
        console.log('Converting image to Base64...', selectedFiles.value[0].file)
        try {
          mediaUrl = await convertFileToBase64(selectedFiles.value[0].file)
          console.log('Image converted to Base64 successfully')
        } catch (conversionError) {
          console.error('Base64 conversion failed:', conversionError)
          throw conversionError
        }
      }
      
      const postData = {
        content: postContent.value.trim(),
        media: mediaUrl
      }
      
      emit('share', postData)
      
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
      console.error('Error creating post:', error)
      alert('Erreur lors de l\'upload de l\'image. Veuillez réessayer.')
    }
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

// Focus sur le textarea quand la modale s'ouvre
watch(() => props.isVisible, (newValue) => {
  if (newValue) {
    nextTick(() => {
      if (textareaRef.value) {
        textareaRef.value.focus()
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

.share-btn {
  background-color: #0095f6;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.share-btn:hover:not(:disabled) {
  background-color: #0081d6;
}

.share-btn:disabled {
  background-color: #c7c7c7;
  cursor: not-allowed;
}

/* Styles pour les aperçus de médias */
.media-preview {
  margin-top: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.media-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
  .modal-overlay {
    padding: 10px;
  }
  
  .modal-content {
    margin: 0;
    max-width: 100%;
    max-height: 95vh;
    border-radius: 8px;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 16px;
    padding-right: 16px;
  }
  
  .modal-header {
    padding-top: 16px;
    padding-bottom: 12px;
  }
  
  .modal-body {
    padding-top: 16px;
    padding-bottom: 16px;
  }
  
  .modal-footer {
    padding-top: 12px;
    padding-bottom: 16px;
  }
  
  .post-textarea {
    font-size: 16px; /* Évite le zoom sur iOS */
    min-height: 80px;
  }
  
  .attachment-btn {
    padding: 6px 10px;
    font-size: 13px;
  }
  
  .share-btn {
    padding: 8px 16px;
    font-size: 13px;
  }
}

@media (max-width: 640px) {
  .modal-overlay {
    padding: 5px;
  }
  
  .modal-content {
    max-height: 98vh;
    border-radius: 6px;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 12px;
    padding-right: 12px;
  }
  
  .modal-header h3 {
    font-size: 16px;
  }
  
  .post-textarea {
    font-size: 15px;
    min-height: 70px;
  }
  
  .modal-footer {
    flex-direction: column;
    gap: 8px;
  }
  
  .attachment-btn,
  .share-btn {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .modal-overlay {
    padding: 0;
  }
  
  .modal-content {
    max-height: 100vh;
    border-radius: 0;
    margin: 0;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 10px;
    padding-right: 10px;
  }
  
  .modal-header {
    padding-top: 12px;
  }
  
  .modal-footer {
    padding-bottom: 12px;
  }
}
</style>
