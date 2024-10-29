import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import type { Router } from 'vue-router';

// Configure axios defaults
axios.defaults.withCredentials = true;
axios.defaults.baseURL = 'http://localhost:8000/api';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

// Create axios instance with base configuration
const axiosInstance = axios.create({
  baseURL: 'http://localhost:8000/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

export const useAuthStore = defineStore('auth', () => {
  console.log('Initializing auth store');
  console.log('Stored user:', localStorage.getItem('user'));
  console.log('Stored auth:', localStorage.getItem('auth'));

  const user = ref(null);
  const token = ref(null);
  let router: Router | null = null;

  // Try to load stored data
  try {
    const storedUser = localStorage.getItem('user');
    const storedAuth = localStorage.getItem('auth');
    
    if (storedUser && storedAuth) {
      user.value = JSON.parse(storedUser);
      const auth = JSON.parse(storedAuth);
      token.value = auth.authorization?.token;
    }
  } catch (error) {
    console.error('Error loading stored auth data:', error);
    localStorage.removeItem('user');
    localStorage.removeItem('auth');
  }

  // Set auth header if token exists
  if (token.value) {
    axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
  }

  const setRouter = (r: Router) => {
    router = r;
  };

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
    localStorage.clear();
    delete axiosInstance.defaults.headers.common['Authorization'];
    
    document.cookie.split(";").forEach(function(c) { 
      document.cookie = c.replace(/^ +/, "")
        .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
    });

    console.log('Auth state cleared');
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
      // Try to call logout API
      await axiosInstance.post('/auth/logout').catch(() => {
        // Ignore API errors during logout
        console.log('Logout API call failed, continuing with local cleanup');
      });
    } finally {
      // Always clear local state, even if API call fails
      clearAuthState();
      if (router) {
        await router.push('/login');
      }
    }
  };

  const isAuthenticated = () => {
    const hasValidToken = !!token.value && typeof token.value === 'string';
    const hasValidUser = !!user.value && 
                        typeof user.value === 'object' && 
                        'id' in user.value;

    console.log('Authentication check:', {
      hasValidToken,
      hasValidUser,
      token: token.value,
      user: user.value
    });

    return hasValidToken && hasValidUser;
  };

  const verifyEmail = async (code: string) => {
    try {
      const response = await axiosInstance.post('/email/verify', { code });
      if (response.data.message === 'Email verified successfully') {
        // Update user state to reflect verification
        const userResponse = await axiosInstance.get('/auth/user');
        user.value = userResponse.data.user;
      }
      return response.data;
    } catch (error) {
      throw error;
    }
  };

  const resendVerification = async () => {
    try {
      const response = await axiosInstance.post('/email/resend');
      return response.data;
    } catch (error) {
      throw error;
    }
  };

  const isVerified = () => {
    return !!user.value?.email_verified_at;
  };

  const forgotPassword = async (email: string) => {
    try {
      const response = await axiosInstance.post('/forgot-password', { email });
      return response.data;
    } catch (error) {
      throw error;
    }
  };

  const verifyResetCode = async (email: string, code: string) => {
    try {
      console.log('Verifying code:', { email, code });
      const response = await axiosInstance.post('/verify-reset-code', { 
        email,
        code 
      });
      console.log('Verification response:', response.data);
      return response.data;
    } catch (error) {
      console.error('Verification error:', error);
      throw error;
    }
  };

  const resetPassword = async (data: {
    email: string;
    token: string;
    password: string;
    password_confirmation: string;
  }) => {
    try {
      console.log('Resetting password with data:', data);
      const response = await axiosInstance.post('/reset-password', data);
      return response.data;
    } catch (error) {
      console.error('Reset password error:', error);
      throw error;
    }
  };

  return {
    user,
    token,
    login,
    register,
    logout,
    isAuthenticated,
    isVerified,
    verifyEmail,
    resendVerification,
    setRouter,
    clearAuthState,
    forgotPassword,
    verifyResetCode,
    resetPassword
  };
});

// Add type safety
interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string;
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
      if (authStore.router) {
        await authStore.router.push('/login');
      }
    }
    return Promise.reject(error);
  }
);

