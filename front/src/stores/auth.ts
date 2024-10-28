import { defineStore } from 'pinia';
import { ref, markRaw } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

// Configure axios defaults
axios.defaults.withCredentials = true;
axios.defaults.baseURL = 'http://localhost:8000/api';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

// Create axios instance with base configuration
const axiosInstance = axios.create({
  baseURL: 'http://localhost:8000/api', // Remove /auth from here
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

export const useAuthStore = defineStore('auth', () => {
  const router = markRaw(useRouter()); // Mark router as raw to avoid reactivity issues

  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'));
  const token = ref(null);

  // Initialize token from localStorage
  const savedAuth = localStorage.getItem('auth');
  if (savedAuth) {
    const auth = JSON.parse(savedAuth);
    token.value = auth.authorization?.token;
  }

  // Set auth header if token exists
  if (token.value) {
    axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
  }

  // Helper function to handle API responses
  const handleAuthResponse = (response: any) => {
    if (response.data.status === 'success' && response.data.user && response.data.authorization) {
      user.value = response.data.user;
      token.value = response.data.authorization.token;
      localStorage.setItem('user', JSON.stringify(response.data.user));
      localStorage.setItem('auth', JSON.stringify({
        authorization: response.data.authorization
      }));
      axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${response.data.authorization.token}`;
      return response;
    }
    throw new Error('Invalid response format');
  };

  // Helper function to clear auth state
  const clearAuthState = () => {
    user.value = null;
    token.value = null;
    localStorage.removeItem('user');
    localStorage.removeItem('auth');
    delete axiosInstance.defaults.headers.common['Authorization'];
  };

  const login = async (email: string, password: string) => {
    try {
      const response = await axiosInstance.post('/auth/login', { email, password });
      return handleAuthResponse(response);
    } catch (error) {
      clearAuthState();
      throw error;
    }
  };

  const register = async (userData: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) => {
    try {
      const response = await axiosInstance.post('/auth/register', userData);
      return handleAuthResponse(response);
    } catch (error) {
      clearAuthState();
      throw error;
    }
  };

  const logout = async () => {
    try {
      await axiosInstance.post('/auth/logout');
      clearAuthState();
      await router.push('/login');
    } catch (error) {
      console.error('Logout error:', error);
      throw error;
    }
  };

  const isAuthenticated = () => {
    return !!token.value && !!user.value;
  };

  return {
    user,
    token,
    login,
    register,
    logout,
    isAuthenticated
  };
});

// Add type safety
interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

interface AuthResponse {
  status: string;
  user: User;
  authorization: {
    token: string;
    type: string;
  };
}

// Add error handling for token expiration
axios.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore();
      await authStore.logout();
      router.push('/login');
    }
    return Promise.reject(error);
  }
);
