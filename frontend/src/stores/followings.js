import { defineStore } from 'pinia'
import { getFollowings } from '../api'

export const useFollowingsStore = defineStore('followings', {
  state: () => ({
    followings: [],
    isLoading: false,
    error: null
  }),

  actions: {
    async fetchFollowings() {
      this.isLoading = true
      this.error = null
      try {
        const response = await getFollowings()
        this.followings = response.data
        console.log('Followings loaded:', this.followings)
      } catch (error) {
        console.error('Error loading followings:', error)
        this.error = error.response?.data?.message || 'Failed to load followings'
      } finally {
        this.isLoading = false
      }
    },

    async refreshFollowings() {
      await this.fetchFollowings()
    },

    clearError() {
      this.error = null
    }
  }
})
