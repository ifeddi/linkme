<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import Navbar from './components/Navbar.vue'
import PostModal from './components/PostModal.vue'

const route = useRoute()
const authStore = useAuthStore()

// Pages qui ne doivent pas afficher la navbar
const hideNavbarPages = ['/login', '/register', '/verify-email', '/forgot-password', '/reset-password']
const shouldShowNavbar = computed(() => {
  return !hideNavbarPages.includes(route.path)
})

// Gestion de la modale de post
const isPostModalVisible = ref(false)

const openPostModal = () => {
  isPostModalVisible.value = true
}

const closePostModal = () => {
  isPostModalVisible.value = false
}

const handlePostShare = async (postData) => {
  try {
    const { usePostsStore } = await import('./stores/posts')
    const postsStore = usePostsStore()
    await postsStore.addPost(postData)
  } catch (error) {
    console.error('Error creating post:', error)
  }
}

// Initialiser l'authentification au démarrage de l'application
onMounted(() => {
  authStore.initAuth()
  
  // Mettre à jour le statut en ligne toutes les 30 secondes
  if (authStore.isLoggedIn) {
    authStore.updateOnlineStatus()
    const statusInterval = setInterval(() => {
      if (authStore.isLoggedIn) {
        authStore.updateOnlineStatus()
      } else {
        clearInterval(statusInterval)
      }
    }, 30000) // 30 secondes
  }
})
</script>

<template>
  <div id="app">
    <Navbar v-if="shouldShowNavbar" @open-post-modal="openPostModal" />
    <router-view />
    <PostModal 
      :is-visible="isPostModalVisible"
      @close="closePostModal"
      @share="handlePostShare"
    />
  </div>
</template>

<style scoped>
.logo {
  height: 6em;
  padding: 1.5em;
  will-change: filter;
  transition: filter 300ms;
}
.logo:hover {
  filter: drop-shadow(0 0 2em #646cffaa);
}
.logo.vue:hover {
  filter: drop-shadow(0 0 2em #42b883aa);
}
</style>