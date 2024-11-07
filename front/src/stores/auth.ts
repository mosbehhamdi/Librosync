import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import type { Router } from 'vue-router';

// Configure axios defaults
axios.defaults.withCredentials = true;
axios.defaults.baseURL = 'https://librosync.tn/api';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

// Create axios instance with base configuration
const axiosInstance = axios.create({
  baseURL: 'https://librosync.tn/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

export const useAuthStore = defineStore('auth', () => {


  const user = ref<User | null>(null);
  const token = ref<string | null>(null);
  let router: Router | null = null;

  // Initialize auth state
  const initializeAuth = async () => {
    const storedUser = localStorage.getItem('user');
    const storedAuth = localStorage.getItem('auth');
    
    if (storedUser && storedAuth) {
      try {
        user.value = JSON.parse(storedUser);
        const auth = JSON.parse(storedAuth);
        token.value = auth.authorization?.token;
        
        axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
        
        await refreshUser();
        
        if (router && (window.location.pathname === '/login' || window.location.pathname === '/')) {
          if (user.value?.is_admin) {
            await router.push('/admin/dashboard');
          } else {
            await router.push('/books');
          }
        }
        return true;
      } catch (error) {
        console.error('Auth initialization error:', error);
        clearAuthState();
        if (router && window.location.pathname !== '/login') {
          await router.push('/login');
        }
        return false;
      }
    }
    return false;
  };

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
  const handleAuthResponse = (response: any): AuthResponse => {
    if (response.data.status === 'success' && response.data.user && response.data.authorization) {
      user.value = response.data.user;
      token.value = response.data.authorization.token;
      
      localStorage.setItem('user', JSON.stringify(response.data.user));
      localStorage.setItem('auth', JSON.stringify({
        authorization: response.data.authorization
      }));
      
      axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
      return response.data;
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
      if (token.value) {
        await axiosInstance.post('/auth/logout').catch(() => {
          console.log('Logout API call failed, continuing with local cleanup');
        });
      }
    } finally {
      clearAuthState();
      if (router) {
        await router.push('/login');
      }
    }
  };

  const isAuthenticated = computed(() => {
    return !!token.value && !!user.value;
  });

  const isAdmin = computed(() => {
    return user.value?.is_admin === true;
  });

  const isVerified = computed(() => {
    return !!user.value?.email_verified_at;
  });

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

  const refreshUser = async () => {
    try {
      const response = await axiosInstance.get('/auth/user');
      if (response.data.user) {
        user.value = response.data.user;
        localStorage.setItem('user', JSON.stringify(response.data.user));
        return true;
      }
      throw new Error('Failed to refresh user data');
    } catch (error) {
      console.error('Error refreshing user:', error);
      clearAuthState();
      throw error;
    }
  };

  const updateProfile = async (data: any) => {
    try {
      const response = await axiosInstance.put('/profile', data);
      user.value = response.data;
      return response;
    } catch (error) {
      throw error;
    }
  };

  const fetchUserStats = async () => {
    try {
      const response = await axiosInstance.get('/user/stats');
      return response;
    } catch (error) {
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
    isAdmin,
    isVerified,
    verifyEmail,
    resendVerification,
    setRouter,
    clearAuthState,
    forgotPassword,
    verifyResetCode,
    resetPassword,
    initializeAuth,
    refreshUser,
    updateProfile,
    fetchUserStats
  };
});

// Updated interfaces
interface User {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
  email_verified_at: string | null;
}

interface AuthResponse {
  status: string;
  user: User;
  authorization: {
    token: string;
    type: string;
  };
}

// Updated axios interceptor
axiosInstance.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore();
      await authStore.clearAuthState();
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export type { User, AuthResponse };

