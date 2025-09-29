<template>
  <div class="profile-container">
    <div class="profile-content">
      <!-- Header du profil -->
      <div class="profile-header">
         <div class="profile-picture">
           <img 
             :src="currentUserProfile.profilePhoto || defaultAvatar" 
             :alt="currentUserProfile.username"
             class="avatar-image"
           />
         </div>
         
         <div class="profile-info">
           <div class="profile-actions">
             <h1 class="username">{{ currentUserProfile.username }}</h1>
            <div class="action-buttons">
              <button class="edit-profile-btn" @click="openEditModal">
                Edit profile
              </button>
              <button class="settings-btn" @click="openSettings">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="3"></circle>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
              </button>
            </div>
          </div>
          
           <div class="profile-stats">
             <div class="stat">
               <span class="stat-number">{{ currentUserProfile.postsCount }}</span>
               <span class="stat-label">Posts</span>
             </div>
             <div class="stat stat-hoverable" @mouseenter="showFollowersTooltip" @mouseleave="hideTooltip">
               <span class="stat-number">{{ currentUserProfile.followersCount }}</span>
               <span class="stat-label">followers</span>
             </div>
             <div class="stat stat-hoverable" @mouseenter="showFollowingTooltip" @mouseleave="hideTooltip">
               <span class="stat-number">{{ currentUserProfile.followingCount }}</span>
               <span class="stat-label">followings</span>
             </div>
           </div>
           
           <div class="profile-details">
             <h2 class="display-name">{{ currentUserProfile.username }}</h2>
             <p class="bio">{{ currentUserProfile.bio || 'No bio available' }}</p>
           </div>
        </div>
      </div>
      
      <!-- Grille des posts -->
      <div class="posts-grid">
        <div 
          v-for="post in userPosts" 
          :key="post.id" 
          class="post-item"
          @click="viewPost(post)"
        >
          <img 
            v-if="post.media" 
            :src="post.media" 
            :alt="post.content"
            class="post-thumbnail"
          />
          <div v-else class="post-thumbnail text-post">
            <p class="post-preview-text">{{ post.content }}</p>
          </div>
        </div>
        
        <!-- Message si aucun post -->
        <div v-if="userPosts.length === 0" class="no-posts-message">
          <div class="no-posts-content">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
              <circle cx="8.5" cy="8.5" r="1.5"></circle>
              <polyline points="21,15 16,10 5,21"></polyline>
            </svg>
            <h3>No posts yet</h3>
            <p>Share your first post to get started!</p>
          </div>
        </div>
      </div>
    </div>
    
     <!-- Modale d'édition de profil -->
     <EditProfileModal 
       :is-visible="isEditModalVisible"
       :user-profile="currentUserProfile"
       @close="closeEditModal"
       @save="handleProfileUpdate"
     />
     
     <!-- Modale de post -->
     <PostViewModal 
       v-if="selectedPost"
       :is-visible="isPostModalVisible"
       :post="selectedPost"
       @close="closePostModal"
     />
     
     <!-- Tooltip pour les followers/following -->
     <FollowersTooltip
       :is-visible="tooltip.isVisible"
       :users="tooltip.users"
       :total-count="tooltip.totalCount"
       :title="tooltip.title"
       :position="tooltip.position"
       @close="hideTooltip"
     />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { getProfile, getFollowers, getFollowing } from '../api'
import { useAuthStore } from '../stores/auth'
import EditProfileModal from '../components/EditProfileModal.vue'
import PostViewModal from '../components/PostViewModal.vue'
import FollowersTooltip from '../components/FollowersTooltip.vue'

const authStore = useAuthStore()

// Données du profil utilisateur
const userProfile = ref({
  username: '',
  bio: '',
  email: '',
  avatar: null,
  postsCount: 0,
  followersCount: 0,
  followingCount: 0
})

// Utiliser les données du store comme fallback
const currentUserProfile = computed(() => {
  return {
    ...userProfile.value,
    email: userProfile.value.email || authStore.user?.email || '',
    username: userProfile.value.username || authStore.user?.name || '',
    bio: userProfile.value.bio || authStore.user?.bio || '',
    profilePhoto: userProfile.value.avatar || authStore.user?.profilePhoto || null,
    postsCount: userProfile.value.postsCount || authStore.user?.postsCount || 0,
    followersCount: userProfile.value.followersCount || authStore.user?.followersCount || 0,
    followingCount: userProfile.value.followingCount || authStore.user?.followingCount || 0
  }
})

// Posts de l'utilisateur
const userPosts = ref([])
const isPostModalVisible = ref(false)
const selectedPost = ref(null)

const isEditModalVisible = ref(false)
const defaultAvatar = 'https://via.placeholder.com/150x150?text=Avatar'
const isLoading = ref(true)

// Tooltip pour followers/following
const tooltip = ref({
  isVisible: false,
  users: [],
  totalCount: 0,
  title: '',
  position: { x: 0, y: 0 }
})

let tooltipTimeout = null

const loadUserProfile = async () => {
  try {
    isLoading.value = true
    console.log('Loading user profile...')
    const response = await getProfile()
    const userData = response.data
    console.log('Profile data received:', userData)
    
    userProfile.value = {
      id: userData.id,
      username: userData.name || '',
      bio: userData.bio || '',
      email: userData.email || authStore.user?.email || '',
      avatar: userData.profilePhoto || null,
      postsCount: userData.postsCount || 0,
      followersCount: userData.followersCount || 0,
      followingCount: userData.followingCount || 0
    }
    console.log('Profile updated:', userProfile.value)
    
    // Charger les posts de l'utilisateur
    await loadUserPosts()
  } catch (error) {
    console.error('Error loading profile:', error)
    // En cas d'erreur, utiliser les données du store comme fallback
    if (authStore.user) {
      userProfile.value = {
        username: authStore.user.name || '',
        bio: authStore.user.bio || '',
        email: authStore.user.email || localStorage.getItem('userEmail') || '',
        avatar: authStore.user.profilePhoto || null,
        postsCount: authStore.user.postsCount || 0,
        followersCount: authStore.user.followersCount || 0,
        followingCount: authStore.user.followingCount || 0
      }
    } else {
      userProfile.value.email = authStore.user?.email || localStorage.getItem('userEmail') || ''
    }
  } finally {
    isLoading.value = false
  }
}

const loadUserPosts = async () => {
  try {
    const { usePostsStore } = await import('../stores/posts')
    const postsStore = usePostsStore()
    
    // Utiliser l'ID de l'utilisateur depuis le profil chargé
    const userId = userProfile.value.id || authStore.user?.id
    console.log('Loading user posts for userId:', userId)
    console.log('User profile:', userProfile.value)
    console.log('Auth store user:', authStore.user)
    
    if (userId) {
      const { getUserPosts } = await import('../api')
      const response = await getUserPosts(userId)
      userPosts.value = response.data
      console.log('User posts loaded:', userPosts.value)
      console.log('First post media:', userPosts.value[0]?.media)
      console.log('First post structure:', userPosts.value[0])
    } else {
      console.warn('No user ID available for loading posts')
    }
  } catch (error) {
    console.error('Error loading user posts:', error)
    console.error('Error details:', error.response?.data)
  }
}

const openEditModal = () => {
  isEditModalVisible.value = true
}

const closeEditModal = () => {
  isEditModalVisible.value = false
}

const openSettings = () => {
  // TODO: Implémenter la page des paramètres
  console.log('Settings clicked')
}

const viewPost = (post) => {
  selectedPost.value = post
  isPostModalVisible.value = true
}

const closePostModal = () => {
  isPostModalVisible.value = false
  selectedPost.value = null
}

const handleProfileUpdate = (updatedProfile) => {
  userProfile.value = { ...userProfile.value, ...updatedProfile }
  
  // Mettre à jour le store avec toutes les données mises à jour
  if (authStore.user) {
    authStore.user = { 
      ...authStore.user, 
      name: updatedProfile.username,
      bio: updatedProfile.bio,
      email: updatedProfile.email,
      profilePhotoUrl: updatedProfile.avatar,
      postsCount: updatedProfile.postsCount,
      followersCount: updatedProfile.followersCount,
      followingCount: updatedProfile.followingCount
    }
  }
  
  // Mettre à jour localStorage
  if (updatedProfile.email) {
    localStorage.setItem('userEmail', updatedProfile.email)
  }
  
  closeEditModal()
}

// Fonctions pour le tooltip
const showFollowersTooltip = async (event) => {
  // Annuler le timeout de masquage s'il existe
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
    tooltipTimeout = null
  }
  
  console.log('showFollowersTooltip called', { userId: authStore.user?.id })
  if (!authStore.user?.id) return
  
  try {
    console.log('Fetching followers for user:', authStore.user.id)
    const response = await getFollowers(authStore.user.id)
    console.log('Followers response:', response.data)
    const { users, total } = response.data
    
    const rect = event.target.getBoundingClientRect()
    const tooltipHeight = 200
    const viewportHeight = window.innerHeight
    
    // Toujours positionner au-dessus pour ne pas cacher l'élément
    let y = rect.top - tooltipHeight - 10
    
    // Si pas assez de place au-dessus, positionner en dessous
    if (y < 10) {
      y = rect.bottom + 10
    }
    
    // S'assurer que le tooltip ne sorte pas en bas
    if (y + tooltipHeight > viewportHeight) {
      y = viewportHeight - tooltipHeight - 10
    }
    
    tooltip.value = {
      isVisible: true,
      users: users,
      totalCount: total,
      title: 'Followers',
      position: {
        x: rect.left,
        y: y
      }
    }
    console.log('Tooltip set:', tooltip.value)
  } catch (error) {
    console.error('Error loading followers:', error)
  }
}

const showFollowingTooltip = async (event) => {
  // Annuler le timeout de masquage s'il existe
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
    tooltipTimeout = null
  }
  
  console.log('showFollowingTooltip called', { userId: authStore.user?.id })
  if (!authStore.user?.id) return
  
  try {
    console.log('Fetching following for user:', authStore.user.id)
    const response = await getFollowing(authStore.user.id)
    console.log('Following response:', response.data)
    const { users, total } = response.data
    
    const rect = event.target.getBoundingClientRect()
    const tooltipHeight = 200
    const viewportHeight = window.innerHeight
    
    // Toujours positionner au-dessus pour ne pas cacher l'élément
    let y = rect.top - tooltipHeight - 10
    
    // Si pas assez de place au-dessus, positionner en dessous
    if (y < 10) {
      y = rect.bottom + 10
    }
    
    // S'assurer que le tooltip ne sorte pas en bas
    if (y + tooltipHeight > viewportHeight) {
      y = viewportHeight - tooltipHeight - 10
    }
    
    tooltip.value = {
      isVisible: true,
      users: users,
      totalCount: total,
      title: 'Following',
      position: {
        x: rect.left,
        y: y
      }
    }
    console.log('Tooltip set:', tooltip.value)
  } catch (error) {
    console.error('Error loading following:', error)
  }
}

const hideTooltip = () => {
  // Ajouter un délai pour éviter les disparitions rapides
  tooltipTimeout = setTimeout(() => {
    tooltip.value.isVisible = false
  }, 100)
}

// Recharger le profil quand l'utilisateur se connecte
watch(() => authStore.isLoggedIn, (isLoggedIn) => {
  if (isLoggedIn) {
    loadUserProfile()
  }
})

onMounted(() => {
  if (authStore.isLoggedIn) {
    loadUserProfile()
  }
})
</script>

<style scoped>
.profile-container {
  min-height: calc(100vh - 60px);
  background-color: #fafafa;
  padding: 20px;
}

.profile-content {
  max-width: 935px;
  margin: 0 auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.profile-header {
  display: flex;
  padding: 30px;
  gap: 30px;
  border-bottom: 1px solid #e1e5e9;
}

.profile-picture {
  flex-shrink: 0;
}

.avatar-image {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e1e5e9;
}

.profile-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.profile-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.username {
  font-size: 28px;
  font-weight: 300;
  margin: 0;
  color: #333;
}

.action-buttons {
  display: flex;
  align-items: center;
  gap: 12px;
}

.edit-profile-btn {
  background: #f5f5f5;
  border: 1px solid #dbdbdb;
  padding: 8px 16px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.edit-profile-btn:hover {
  background: #e8e8e8;
}

.settings-btn {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s ease;
}

.settings-btn:hover {
  background-color: #f5f5f5;
}

.settings-btn svg {
  color: #333;
}

.profile-stats {
  display: flex;
  gap: 40px;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.stat-number {
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.stat-label {
  font-size: 14px;
  color: #666;
}

.profile-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.display-name {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  color: #333;
}

.bio {
  font-size: 14px;
  color: #333;
  margin: 0;
  line-height: 1.4;
}

.posts-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
  min-height: 200px;
}

.post-item {
  aspect-ratio: 1;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  background: #f5f5f5;
}

.post-thumbnail {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.2s ease;
}

.post-item:hover .post-thumbnail {
  transform: scale(1.05);
}

.no-posts-message {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  background: #fafafa;
  border-radius: 8px;
  margin: 20px;
}

.no-posts-content {
  text-align: center;
  color: #666;
}

.no-posts-content svg {
  color: #ccc;
  margin-bottom: 16px;
}

.no-posts-content h3 {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.no-posts-content p {
  margin: 0;
  font-size: 14px;
  color: #666;
}

.text-post {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  padding: 16px;
}

.post-preview-text {
  margin: 0;
  color: #666;
  font-size: 14px;
  text-align: center;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Responsive */
@media (max-width: 768px) {
  .profile-container {
    padding: 15px;
    min-height: calc(100vh - 50px);
  }
  
  .profile-content {
    border-radius: 6px;
  }
  
  .profile-header {
    flex-direction: column;
    text-align: center;
    padding: 20px;
    gap: 20px;
  }
  
  .avatar-image {
    width: 120px;
    height: 120px;
  }
  
  .profile-actions {
    flex-direction: column;
    gap: 12px;
  }
  
  .btn {
    padding: 8px 16px;
    font-size: 14px;
  }
  
  .profile-stats {
    justify-content: center;
    gap: 20px;
  }
  
  .stat-item {
    padding: 8px 12px;
  }
  
  .stat-number {
    font-size: 16px;
  }
  
  .stat-label {
    font-size: 12px;
  }
  
  .posts-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 2px;
  }
  
  .no-posts-message {
    margin: 10px;
    min-height: 150px;
  }
}

@media (max-width: 640px) {
  .profile-container {
    padding: 10px;
  }
  
  .profile-header {
    padding: 15px;
    gap: 15px;
  }
  
  .avatar-image {
    width: 100px;
    height: 100px;
  }
  
  .profile-actions {
    gap: 10px;
  }
  
  .btn {
    padding: 7px 14px;
    font-size: 13px;
  }
  
  .profile-stats {
    gap: 15px;
  }
  
  .stat-item {
    padding: 6px 10px;
  }
  
  .stat-number {
    font-size: 15px;
  }
  
  .stat-label {
    font-size: 11px;
  }
  
  .posts-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .profile-container {
    padding: 8px;
  }
  
  .profile-content {
    border-radius: 4px;
  }
  
  .profile-header {
    padding: 12px;
    gap: 12px;
  }
  
  .avatar-image {
    width: 80px;
    height: 80px;
  }
  
  .profile-actions {
    gap: 8px;
  }
  
  .btn {
    padding: 6px 12px;
    font-size: 12px;
  }
  
  .profile-stats {
    gap: 10px;
    flex-wrap: wrap;
  }
  
  .stat-item {
    padding: 5px 8px;
    min-width: 60px;
  }
  
  .stat-number {
    font-size: 14px;
  }
  
  .stat-label {
    font-size: 10px;
  }
  
  .posts-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1px;
  }
  
  .no-posts-message {
    margin: 5px;
    min-height: 120px;
  }
  
  .no-posts-content h3 {
    font-size: 1.1rem;
  }
  
  .no-posts-content p {
    font-size: 13px;
  }
}

/* Styles pour le tooltip */
.stat-hoverable {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.stat-hoverable:hover {
  background-color: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
}
</style>
