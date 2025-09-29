import { defineStore } from "pinia";
import { 
  getNotifications, 
  getUnreadNotificationsCount, 
  markAllNotificationsAsRead 
} from "../api";

export const useNotificationsStore = defineStore("notifications", {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    isLoading: false,
    error: null,
  }),

  getters: {
    hasUnreadNotifications: (state) => state.unreadCount > 0,
  },

  actions: {
    async fetchNotifications() {
      this.isLoading = true;
      this.error = null;
      try {
        const response = await getNotifications();
        this.notifications = response.data;
      } catch (error) {
        console.error("Error fetching notifications:", error);
        this.error = error.response?.data?.message || "Failed to fetch notifications";
      } finally {
        this.isLoading = false;
      }
    },

    async fetchUnreadCount() {
      try {
        const response = await getUnreadNotificationsCount();
        this.unreadCount = response.data.count;
      } catch (error) {
        console.error("Error fetching unread count:", error);
      }
    },

    async markAllAsRead() {
      try {
        await markAllNotificationsAsRead();
        this.unreadCount = 0;
        // Marquer toutes les notifications comme lues dans le state
        this.notifications = this.notifications.map(notification => ({
          ...notification,
          isRead: true
        }));
      } catch (error) {
        console.error("Error marking notifications as read:", error);
      }
    },

    // Méthode pour mettre à jour le compteur en temps réel
    incrementUnreadCount() {
      this.unreadCount += 1;
    },

    // Méthode pour décrémenter le compteur (si nécessaire)
    decrementUnreadCount() {
      if (this.unreadCount > 0) {
        this.unreadCount -= 1;
      }
    },

    clearError() {
      this.error = null;
    },
  },
});
