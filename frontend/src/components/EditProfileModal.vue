<template>
  <div v-if="isVisible" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>Edit Profile</h3>
        <button class="close-btn" @click="closeModal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      
      <div class="modal-body">
        <!-- Photo de profil -->
        <div class="profile-picture-section">
          <div class="current-avatar">
            <img 
              :src="formData.avatar || defaultAvatar" 
              :alt="formData.username"
              class="avatar-preview"
            />
          </div>
          <button class="choose-picture-btn" @click="selectAvatar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
              <circle cx="12" cy="13" r="4"></circle>
            </svg>
            Choose Picture
          </button>
          <input 
            ref="avatarInput"
            type="file" 
            accept="image/*" 
            @change="handleAvatarSelect"
            style="display: none"
          />
        </div>
        
        <!-- Champs de formulaire -->
        <div class="form-fields">
          <div class="form-group">
            <label for="username">Username</label>
            <input 
              id="username"
              v-model="formData.username"
              type="text" 
              placeholder="Username"
              class="form-input"
            />
          </div>
          
          <div class="form-group">
            <label for="bio">Bio</label>
            <textarea 
              id="bio"
              v-model="formData.bio"
              placeholder="Bio"
              class="form-textarea"
              rows="3"
            ></textarea>
          </div>
          
          <div class="form-group">
            <label for="email">Email</label>
            <input 
              id="email"
              v-model="formData.email"
              type="email" 
              placeholder="Email"
              class="form-input"
            />
          </div>
        </div>
        
        <!-- Affichage des erreurs -->
        <div v-if="error" class="error-message">
          {{ error }}
        </div>
      </div>
      
      <div class="modal-footer">
        <button class="save-btn" @click="handleSave" :disabled="!isFormValid || isSaving">
          <span v-if="isSaving">Saving...</span>
          <span v-else>Save</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { updateProfile } from '../api'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  },
  userProfile: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'save'])

const formData = ref({
  username: '',
  bio: '',
  email: '',
  avatar: null
})

const avatarInput = ref(null)
const defaultAvatar = 'https://via.placeholder.com/150x150?text=Avatar'
const isSaving = ref(false)
const error = ref('')
const authStore = useAuthStore()

const isFormValid = computed(() => {
  return formData.value.username.trim() && formData.value.email.trim()
})

// Initialiser les données du formulaire quand la modale s'ouvre
watch(() => props.isVisible, (newValue) => {
  if (newValue) {
    formData.value = {
      username: props.userProfile.username || props.userProfile.name || '',
      bio: props.userProfile.bio || '',
      email: props.userProfile.email || '',
      avatar: props.userProfile.avatar || props.userProfile.profilePhoto || null
    }
    error.value = ''
  }
})

const closeModal = () => {
  emit('close')
}

const selectAvatar = () => {
  avatarInput.value?.click()
}

const convertFileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result)
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

const handleAvatarSelect = async (event) => {
  const file = event.target.files[0]
  if (file) {
    // Vérifier le type de fichier
    if (file.type.startsWith('image/')) {
      // Vérifier la taille (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('L\'image est trop volumineuse. Taille maximum: 5MB')
        return
      }
      
      try {
        // Convertir l'image en Base64
        console.log('Converting avatar to Base64...', file)
        formData.value.avatar = await convertFileToBase64(file)
        console.log('Avatar converted to Base64 successfully')
      } catch (error) {
        console.error('Error uploading avatar:', error)
        alert('Erreur lors de l\'upload de l\'image. Veuillez réessayer.')
      }
    } else {
      alert('Type de fichier non supporté. Seules les images sont acceptées.')
    }
  }
  
  // Réinitialiser l'input
  event.target.value = ''
}

const handleSave = async () => {
  if (!isFormValid.value) return
  
  isSaving.value = true
  error.value = ''
  
  try {
    const profileData = {
      name: formData.value.username.trim(),
      bio: formData.value.bio.trim(),
      email: formData.value.email.trim(),
      profilePhoto: formData.value.avatar
    }
    
    const response = await updateProfile(profileData)
    
    // Mettre à jour le store auth
    authStore.updateUserProfile({
      name: response.data.user.name,
      bio: response.data.user.bio,
      email: response.data.user.email,
      profilePhoto: response.data.user.profilePhoto,
      postsCount: response.data.user.postsCount,
      followersCount: response.data.user.followersCount,
      followingCount: response.data.user.followingCount
    })
    
    // Émettre les données mises à jour
    emit('save', {
      username: response.data.user.name,
      bio: response.data.user.bio,
      email: response.data.user.email,
      avatar: response.data.user.profilePhoto,
      postsCount: response.data.user.postsCount,
      followersCount: response.data.user.followersCount,
      followingCount: response.data.user.followingCount
    })
    
    closeModal()
  } catch (err) {
    console.error('Error updating profile:', err)
    error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du profil'
  } finally {
    isSaving.value = false
  }
}
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
  max-width: 400px;
  max-height: 90vh;
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
  padding: 24px;
  overflow-y: auto;
}

.profile-picture-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 24px;
}

.current-avatar {
  margin-bottom: 16px;
}

.avatar-preview {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e1e5e9;
}

.choose-picture-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #333;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.choose-picture-btn:hover {
  background: #555;
}

.choose-picture-btn svg {
  color: white;
}

.form-fields {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #dbdbdb;
  border-radius: 6px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
  border-color: #0095f6;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.modal-footer {
  padding: 16px 24px 20px;
  border-top: 1px solid #e1e5e9;
  display: flex;
  justify-content: flex-end;
}

.save-btn {
  background: #333;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.save-btn:hover:not(:disabled) {
  background: #555;
}

.save-btn:disabled {
  background: #c7c7c7;
  cursor: not-allowed;
}

.error-message {
  background-color: #f8d7da;
  color: #721c24;
  padding: 12px;
  border-radius: 6px;
  font-size: 14px;
  margin-top: 16px;
  border: 1px solid #f5c6cb;
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
    max-height: 95vh;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 20px;
    padding-right: 20px;
  }
}
</style>
