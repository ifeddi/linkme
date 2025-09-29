<template>
  <div class="profile-container" v-if="loaded">
    <div class="profile-content">
      <div class="profile-header">
        <div class="profile-picture">
          <img :src="profile.profilePhoto || defaultAvatar" class="avatar-image" />
        </div>
        <div class="profile-info">
          <div class="profile-actions">
            <h1 class="username">{{ profile.username }}</h1>
            <div class="action-buttons">
              <button v-if="!profile.isSelf" class="edit-profile-btn" @click="toggleFollowUser" :disabled="toggling">
                {{ getFollowButtonText() }}
              </button>
            </div>
          </div>
          <div class="profile-stats">
            <div class="stat"><span class="stat-number">{{ profile.postsCount }}</span><span class="stat-label">Posts</span></div>
            <div class="stat stat-hoverable" @mouseenter="showFollowersTooltip" @mouseleave="hideTooltip">
              <span class="stat-number">{{ profile.followersCount }}</span>
              <span class="stat-label">followers</span>
            </div>
            <div class="stat stat-hoverable" @mouseenter="showFollowingTooltip" @mouseleave="hideTooltip">
              <span class="stat-number">{{ profile.followingCount }}</span>
              <span class="stat-label">followings</span>
            </div>
          </div>
          <div class="profile-details">
            <h2 class="display-name">{{ profile.username }}</h2>
            <p class="bio">{{ profile.bio || 'No bio available' }}</p>
          </div>
        </div>
      </div>

      <div class="posts-grid" v-if="profile.followStatus === 'accepted' || profile.isSelf">
        <div v-for="post in userPosts" :key="post.id" class="post-item">
          <img v-if="post.media" :src="post.media" class="post-thumbnail" />
          <div v-else class="post-thumbnail text-post"><p class="post-preview-text">{{ post.content }}</p></div>
        </div>
      </div>
    </div>
    
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
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getPublicProfile, toggleFollow, getUserPosts, getFollowers, getFollowing } from '../api'
import { usePostsStore } from '../stores/posts'
import FollowersTooltip from '../components/FollowersTooltip.vue'

const route = useRoute()
const postsStore = usePostsStore()
const profile = ref(null)
const userPosts = ref([])
const loaded = ref(false)
const toggling = ref(false)
const defaultAvatar = 'https://via.placeholder.com/150x150?text=Avatar'

// Tooltip pour followers/following
const tooltip = ref({
  isVisible: false,
  users: [],
  totalCount: 0,
  title: '',
  position: { x: 0, y: 0 }
})

let tooltipTimeout = null

const load = async () => {
  try {
    const username = route.params.username
    console.log('Loading profile for username:', username)
    const { data } = await getPublicProfile(username)
    profile.value = data
    console.log('Profile loaded:', profile.value)
    
    if (profile.value.followStatus === 'accepted' || profile.value.isSelf) {
      const { data: posts } = await getUserPosts(profile.value.id)
      userPosts.value = posts
      console.log('User posts loaded in UserProfile:', posts)
      console.log('First post media:', posts[0]?.media)
    } else {
      userPosts.value = []
    }
    loaded.value = true
  } catch (error) {
    console.error('Error loading user profile:', error)
    console.error('Error details:', error.response?.data)
    // Afficher un message d'erreur ou rediriger
    loaded.value = true
  }
}

const getFollowButtonText = () => {
  if (!profile.value) return 'Follow'
  
  switch (profile.value.followStatus) {
    case 'pending':
      return 'Pending'
    case 'accepted':
      return 'Unfollow'
    default:
      return 'Follow'
  }
}

const toggleFollowUser = async () => {
  if (!profile.value) return
  toggling.value = true
  try {
    const { data } = await toggleFollow(profile.value.id)
    profile.value.followStatus = data.followStatus
    
    // Recharger les posts si le follow est accepté
    if (data.followStatus === 'accepted') {
      const { data: posts } = await getUserPosts(profile.value.id)
      userPosts.value = posts
      console.log('Posts loaded after follow:', posts)
      console.log('First post media after follow:', posts[0]?.media)
    } else {
      userPosts.value = []
    }
    
    // Recharger les posts de la page Home pour refléter les changements de follow
    await postsStore.refreshPosts()
  } finally {
    toggling.value = false
  }
}

// Fonctions pour le tooltip
const showFollowersTooltip = async (event) => {
  // Annuler le timeout de masquage s'il existe
  if (tooltipTimeout) {
    clearTimeout(tooltipTimeout)
    tooltipTimeout = null
  }
  
  if (!profile.value?.id) return
  
  try {
    const response = await getFollowers(profile.value.id)
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
  
  if (!profile.value?.id) return
  
  try {
    const response = await getFollowing(profile.value.id)
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

onMounted(load)
</script>

<style scoped>
.profile-container { min-height: calc(100vh - 60px); background-color: #fafafa; padding: 20px; }
.profile-content { max-width: 935px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
.profile-header { display: flex; padding: 30px; gap: 30px; border-bottom: 1px solid #e1e5e9; }
.avatar-image { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 2px solid #e1e5e9; }
.profile-info { flex: 1; display: flex; flex-direction: column; gap: 20px; }
.profile-actions { display: flex; align-items: center; justify-content: space-between; }
.username { font-size: 28px; font-weight: 300; margin: 0; color: #333; }
.action-buttons { display: flex; align-items: center; gap: 12px; }
.edit-profile-btn { background: #f5f5f5; border: 1px solid #dbdbdb; padding: 8px 16px; border-radius: 4px; font-size: 14px; font-weight: 600; cursor: pointer; }
.profile-stats { display: flex; gap: 40px; }
.stat { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.stat-number { font-size: 18px; font-weight: 600; color: #333; }
.stat-label { font-size: 14px; color: #666; }
.posts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; }
.post-item { aspect-ratio: 1; overflow: hidden; background: #f5f5f5; }
.post-thumbnail { width: 100%; height: 100%; object-fit: cover; }
.text-post { display: flex; align-items: center; justify-content: center; padding: 16px; }
.post-preview-text { color: #666; font-size: 14px; text-align: center; }

/* Styles pour le tooltip */
.stat-hoverable {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.stat-hoverable:hover {
  background-color: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
}

/* Responsive Design */
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
  
  .username {
    font-size: 24px;
  }
  
  .action-buttons {
    gap: 8px;
  }
  
  .edit-profile-btn {
    padding: 6px 12px;
    font-size: 13px;
  }
  
  .profile-stats {
    gap: 20px;
    justify-content: center;
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
  
  .username {
    font-size: 20px;
  }
  
  .action-buttons {
    gap: 6px;
  }
  
  .edit-profile-btn {
    padding: 5px 10px;
    font-size: 12px;
  }
  
  .profile-stats {
    gap: 15px;
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
  
  .username {
    font-size: 18px;
  }
  
  .action-buttons {
    gap: 4px;
  }
  
  .edit-profile-btn {
    padding: 4px 8px;
    font-size: 11px;
  }
  
  .profile-stats {
    gap: 10px;
    flex-wrap: wrap;
  }
  
  .stat {
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
}
</style>


