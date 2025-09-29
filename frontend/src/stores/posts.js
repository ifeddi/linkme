import { defineStore } from "pinia";
import { 
  getPosts, 
  createPost, 
  updatePost as updatePostAPI, 
  deletePost, 
  getUserPosts, 
  toggleLike, 
  getLikeStatus, 
  getComments, 
  createComment, 
  deleteComment 
} from "../api";

export const usePostsStore = defineStore("posts", {
  state: () => ({
    posts: [],
    userPosts: [],
    isLoading: false,
    error: null,
  }),

  getters: {
    getPostById: (state) => (id) => {
      return state.posts.find(post => post.id === id);
    },
    getUserPostById: (state) => (id) => {
      return state.userPosts.find(post => post.id === id);
    },
  },

  actions: {
    async fetchPosts() {
      this.isLoading = true;
      this.error = null;
      try {
        const response = await getPosts();
        this.posts = response.data;
      } catch (error) {
        console.error("Error fetching posts:", error);
        this.error = error.response?.data?.message || "Failed to fetch posts";
      } finally {
        this.isLoading = false;
      }
    },

    async refreshPosts() {
      // Recharger les posts sans afficher le loading (pour les mises à jour silencieuses)
      try {
        const response = await getPosts();
        this.posts = response.data;
      } catch (error) {
        console.error("Error refreshing posts:", error);
        // Ne pas afficher l'erreur pour les rafraîchissements silencieux
      }
    },

    async fetchUserPosts(userId) {
      this.isLoading = true;
      this.error = null;
      try {
        const response = await getUserPosts(userId);
        this.userPosts = response.data;
      } catch (error) {
        console.error("Error fetching user posts:", error);
        this.error = error.response?.data?.message || "Failed to fetch user posts";
      } finally {
        this.isLoading = false;
      }
    },

    async addPost(postData) {
      try {
        const response = await createPost(postData);
        this.posts.unshift(response.data); // Ajouter au début de la liste
        return response.data;
      } catch (error) {
        console.error("Error creating post:", error);
        this.error = error.response?.data?.message || "Failed to create post";
        throw error;
      }
    },

    async updatePost(postId, postData) {
      try {
        const response = await updatePostAPI(postId, postData);
        
        // Mettre à jour le post dans la liste des posts
        const postIndex = this.posts.findIndex(post => post.id === postId);
        if (postIndex !== -1) {
          this.posts[postIndex] = response.data;
        }
        
        // Mettre à jour le post dans la liste des posts utilisateur
        const userPostIndex = this.userPosts.findIndex(post => post.id === postId);
        if (userPostIndex !== -1) {
          this.userPosts[userPostIndex] = response.data;
        }
        
        return response.data;
      } catch (error) {
        console.error("Error updating post:", error);
        this.error = error.response?.data?.message || "Failed to update post";
        throw error;
      }
    },

    async removePost(postId) {
      try {
        await deletePost(postId);
        this.posts = this.posts.filter(post => post.id !== postId);
        this.userPosts = this.userPosts.filter(post => post.id !== postId);
      } catch (error) {
        console.error("Error deleting post:", error);
        this.error = error.response?.data?.message || "Failed to delete post";
        throw error;
      }
    },

    async togglePostLike(postId) {
      try {
        console.log('Store: Toggling like for post:', postId);
        const response = await toggleLike(postId);
        console.log('Store: Like response:', response.data);
        
        const post = this.posts.find(p => p.id === postId);
        const userPost = this.userPosts.find(p => p.id === postId);
        
        if (post) {
          post.likesCount = response.data.likesCount;
          console.log('Store: Updated post likes count:', post.likesCount);
        }
        if (userPost) {
          userPost.likesCount = response.data.likesCount;
          console.log('Store: Updated user post likes count:', userPost.likesCount);
        }
        
        return response.data;
      } catch (error) {
        console.error("Store: Error toggling like:", error);
        console.error("Store: Error details:", error.response?.data);
        this.error = error.response?.data?.message || "Failed to toggle like";
        throw error;
      }
    },

    async getPostComments(postId) {
      try {
        const response = await getComments(postId);
        return response.data;
      } catch (error) {
        console.error("Error fetching comments:", error);
        this.error = error.response?.data?.message || "Failed to fetch comments";
        throw error;
      }
    },

    async addComment(postId, content) {
      try {
        const response = await createComment(postId, content);
        
        // Mettre à jour le compteur de commentaires
        const post = this.posts.find(p => p.id === postId);
        const userPost = this.userPosts.find(p => p.id === postId);
        
        if (post) {
          post.commentsCount = (post.commentsCount || 0) + 1;
        }
        if (userPost) {
          userPost.commentsCount = (userPost.commentsCount || 0) + 1;
        }
        
        return response.data;
      } catch (error) {
        console.error("Error creating comment:", error);
        this.error = error.response?.data?.message || "Failed to create comment";
        throw error;
      }
    },

    async removeComment(commentId, postId) {
      try {
        await deleteComment(commentId);
        
        // Mettre à jour le compteur de commentaires
        const post = this.posts.find(p => p.id === postId);
        const userPost = this.userPosts.find(p => p.id === postId);
        
        if (post) {
          post.commentsCount = Math.max(0, post.commentsCount - 1);
        }
        if (userPost) {
          userPost.commentsCount = Math.max(0, userPost.commentsCount - 1);
        }
      } catch (error) {
        console.error("Error deleting comment:", error);
        this.error = error.response?.data?.message || "Failed to delete comment";
        throw error;
      }
    },

    clearError() {
      this.error = null;
    },
  },
});
