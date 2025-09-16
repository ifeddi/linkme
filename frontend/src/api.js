// frontend/src/api.js
import axios from 'axios'

export const API_HOST = 'http://localhost:8000' // Change to your API host

export const api = axios.create({
	baseURL: API_HOST,
})
export const register = (password, email) => {
	return api.post('/api/user/register', { password, email })
}