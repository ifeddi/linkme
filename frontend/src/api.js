// frontend/src/api.js
import axios from 'axios'

export const API_HOST = 'http://localhost:8000' // Change to your API host

export const api = axios.create({
	baseURL: API_HOST,
})

// Ajouter le token d'authentification aux requêtes
api.interceptors.request.use((config) => {
	const token = localStorage.getItem('token')
	if (token) {
		config.headers.Authorization = `Bearer ${token}`
	}
	return config
})

export const register = (password, email, name) => {
    return api.post('/api/user/register', { password, email, name })
}

export const getProfile = () => {
	return api.get('/api/user/profile')
}

export const updateProfile = (profileData) => {
	return api.put('/api/user/profile', profileData)
}

// Posts API
export const getPosts = () => {
	return api.get('/api/posts')
}

export const createPost = (postData) => {
	return api.post('/api/posts', postData)
}

export const getPost = (id) => {
	return api.get(`/api/posts/${id}`)
}

export const updatePost = (id, postData) => {
	return api.put(`/api/posts/${id}`, postData)
}

export const deletePost = (id) => {
	return api.delete(`/api/posts/${id}`)
}

export const getUserPosts = (userId) => {
	return api.get(`/api/posts/user/${userId}`)
}

// Likes API
export const toggleLike = (postId) => {
	return api.post(`/api/posts/${postId}/like`)
}

export const getLikeStatus = (postId) => {
	return api.get(`/api/posts/${postId}/like`)
}

// Comments API
export const getComments = (postId) => {
	return api.get(`/api/posts/${postId}/comments`)
}

export const createComment = (postId, content) => {
	return api.post(`/api/posts/${postId}/comments`, { content })
}

export const deleteComment = (commentId) => {
	return api.delete(`/api/comments/${commentId}`)
}

// Users API (search/public profile/follow)
export const searchUsers = (q) => {
    return api.get(`/api/users/search`, { params: { q } })
}

export const getPublicProfile = (username) => {
    return api.get(`/api/users/${username}`)
}

export const toggleFollow = (userId) => {
    return api.post(`/api/users/${userId}/follow`)
}

export const acceptFollow = (userId) => {
    return api.post(`/api/users/${userId}/follow/accept`)
}

export const rejectFollow = (userId) => {
    return api.post(`/api/users/${userId}/follow/reject`)
}

export const getFollowings = () => {
    return api.get('/api/user/followings')
}

// Notifications API
export const getNotifications = () => {
    return api.get('/api/notifications')
}

export const getUnreadNotificationsCount = () => {
    return api.get('/api/notifications/unread-count')
}

export const markAllNotificationsAsRead = () => {
    return api.post('/api/notifications/mark-all-read')
}

// Upload API
export const uploadImage = (file) => {
    const formData = new FormData()
    formData.append('image', file)
    return api.post('/api/upload/image', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
}

// Chat API
export const getChatConversations = () => {
    return api.get('/api/chat/conversations')
}

export const getChatMessages = (conversationId) => {
    return api.get(`/api/chat/conversations/${conversationId}/messages`)
}

export const sendChatMessage = (conversationId, messageData) => {
    return api.post(`/api/chat/conversations/${conversationId}/messages`, messageData)
}

export const markChatAsRead = (conversationId) => {
    return api.post(`/api/chat/conversations/${conversationId}/read`)
}

export const getChatStickers = () => {
    return api.get('/api/chat/stickers')
}

export const updateOnlineStatus = () => {
    return api.post('/api/chat/online-status')
}

// Followers/Following API
export const getFollowers = (userId) => {
    return api.get(`/api/users/${userId}/followers`)
}

export const getFollowing = (userId) => {
    return api.get(`/api/users/${userId}/following`)
}

// Likes API
export const getLikesUsers = (postId) => {
    return api.get(`/api/posts/${postId}/likes`)
}