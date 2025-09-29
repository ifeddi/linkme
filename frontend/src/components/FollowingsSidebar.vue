<template>
  <div class="followings-sidebar">
    <div class="sidebar-header">
      <h3>Followings</h3>
    </div>
    
    <div class="followings-list">
      <!-- Message si aucun following -->
      <div v-if="followingsStore.followings.length === 0" class="no-followings">
        <p>No followings yet</p>
      </div>
      
      <!-- Liste des utilisateurs suivis -->
      <div 
        v-for="user in followingsStore.followings" 
        :key="user.id" 
        class="following-item"
      >
        <div class="user-info">
          <img 
            :src="user.profilePhoto || defaultAvatar" 
            :alt="user.username"
            class="user-avatar"
          />
          <span class="username clickable" @click="goToUserProfile(user.username)">{{ user.username }}</span>
        </div>
        
        <button 
          class="unfollow-btn"
          @click="handleUnfollow(user.id)"
          :disabled="isUnfollowing"
        >
          UnFollow
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, defineExpose, watch } from 'vue'
import { useRouter } from 'vue-router'
import { toggleFollow } from '../api'
import { usePostsStore } from '../stores/posts'
import { useFollowingsStore } from '../stores/followings'

const isUnfollowing = ref(false)
const defaultAvatar = 'https://via.placeholder.com/32x32?text=Avatar'
const postsStore = usePostsStore()
const followingsStore = useFollowingsStore()
const router = useRouter()

const loadFollowings = async () => {
  await followingsStore.fetchFollowings()
}

const goToUserProfile = (username) => {
  router.push(`/u/${encodeURIComponent(username)}`)
}

const handleUnfollow = async (userId) => {
  if (isUnfollowing.value) return
  
  isUnfollowing.value = true
  try {
    await toggleFollow(userId)
    
    // Recharger la liste des followings
    await followingsStore.refreshFollowings()
    
    // Recharger les posts pour masquer ceux de l'utilisateur unfollowed
    await postsStore.refreshPosts()
  } catch (error) {
    console.error('Error unfollowing user:', error)
  } finally {
    isUnfollowing.value = false
  }
}

// Exposer la fonction pour qu'elle puisse être appelée depuis l'extérieur
defineExpose({
  loadFollowings
})

onMounted(() => {
  console.log('FollowingsSidebar mounted')
  loadFollowings()
})
</script>

<style scoped>
.followings-sidebar {
  width: 250px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
  height: fit-content;
  position: sticky;
  top: 80px;
}

.sidebar-header {
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e1e5e9;
}

.sidebar-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.followings-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.no-followings {
  text-align: center;
  padding: 20px;
  color: #666;
}

.no-followings p {
  margin: 0;
  font-size: 14px;
}

.following-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.username {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.username.clickable {
  cursor: pointer;
  transition: color 0.2s ease;
}

.username.clickable:hover {
  color: #0095f6;
  text-decoration: underline;
}

.unfollow-btn {
  background: #0095f6;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.unfollow-btn:hover:not(:disabled) {
  background: #0081d6;
}

.unfollow-btn:disabled {
  background: #c7c7c7;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 1024px) {
  .followings-sidebar {
    display: none;
  }
}
</style>
