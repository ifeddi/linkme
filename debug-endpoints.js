// Script de débogage pour tester les endpoints individuellement
const axios = require('axios');

const API_BASE = 'http://localhost:8000';

async function debugEndpoints() {
  console.log('🔍 Debugging API endpoints...\n');
  
  // Test 1: Vérifier les routes disponibles
  console.log('1. Testing available routes...');
  try {
    const response = await axios.get(`${API_BASE}/api/posts`);
    console.log('✅ GET /api/posts works');
    console.log('Response:', response.status, response.data.length, 'posts');
  } catch (error) {
    console.log('❌ GET /api/posts failed:', error.response?.status, error.response?.data);
  }
  
  // Test 2: Tester l'authentification
  console.log('\n2. Testing authentication...');
  try {
    const authResponse = await axios.post(`${API_BASE}/api/login_check`, {
      email: 'test@example.com',
      password: 'password123'
    });
    console.log('✅ Authentication works');
    const token = authResponse.data.token;
    console.log('Token received:', token ? 'Yes' : 'No');
    
    // Test 3: Tester avec le token
    console.log('\n3. Testing authenticated requests...');
    const authHeaders = { Authorization: `Bearer ${token}` };
    
    // Test GET posts avec auth
    try {
      const postsResponse = await axios.get(`${API_BASE}/api/posts`, { headers: authHeaders });
      console.log('✅ GET /api/posts with auth works');
    } catch (error) {
      console.log('❌ GET /api/posts with auth failed:', error.response?.status, error.response?.data);
    }
    
    // Test POST post
    try {
      const postResponse = await axios.post(`${API_BASE}/api/posts`, {
        content: 'Debug test post'
      }, { headers: authHeaders });
      console.log('✅ POST /api/posts works');
      const postId = postResponse.data.id;
      console.log('Post ID:', postId);
      
      // Test like
      try {
        const likeResponse = await axios.post(`${API_BASE}/api/posts/${postId}/like`, {}, { headers: authHeaders });
        console.log('✅ POST /api/posts/{id}/like works');
        console.log('Like response:', likeResponse.data);
      } catch (error) {
        console.log('❌ POST /api/posts/{id}/like failed:', error.response?.status, error.response?.data);
      }
      
      // Test comment
      try {
        const commentResponse = await axios.post(`${API_BASE}/api/posts/${postId}/comments`, {
          content: 'Debug test comment'
        }, { headers: authHeaders });
        console.log('✅ POST /api/posts/{id}/comments works');
        console.log('Comment response:', commentResponse.data);
      } catch (error) {
        console.log('❌ POST /api/posts/{id}/comments failed:', error.response?.status, error.response?.data);
      }
      
      // Nettoyer
      try {
        await axios.delete(`${API_BASE}/api/posts/${postId}`, { headers: authHeaders });
        console.log('✅ DELETE /api/posts/{id} works');
      } catch (error) {
        console.log('❌ DELETE /api/posts/{id} failed:', error.response?.status, error.response?.data);
      }
      
    } catch (error) {
      console.log('❌ POST /api/posts failed:', error.response?.status, error.response?.data);
    }
    
  } catch (error) {
    console.log('❌ Authentication failed:', error.response?.status, error.response?.data);
  }
}

debugEndpoints();
