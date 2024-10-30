import { defineStore } from 'pinia';
import { api } from '@/api';

export const useAdminStore = defineStore('admin', {
  state: () => ({
    users: [],
    stats: {
      total_users: 0,
      verified_users: 0,
      unverified_users: 0
    },
    isLoading: false,
    error: null
  }),

  actions: {
    async fetchUsers() {
      this.isLoading = true;
      try {
        const response = await api.get('/admin/users');
        this.users = response.data.users;
      } catch (error) {
        this.error = error;
      } finally {
        this.isLoading = false;
      }
    },

    async fetchDashboardStats() {
      this.isLoading = true;
      try {
        const response = await api.get('/admin/dashboard');
        this.stats = response.data;
      } catch (error) {
        this.error = error;
      } finally {
        this.isLoading = false;
      }
    },

    async deleteUser(userId: number) {
      try {
        await api.delete(`/admin/users/${userId}`);
        this.users = this.users.filter(user => user.id !== userId);
      } catch (error) {
        this.error = error;
      }
    }
  }
}); 