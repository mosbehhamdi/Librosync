import { defineStore } from 'pinia';
import { api } from '@/api';

export const useAdminStore = defineStore('admin', {
  state: () => ({
    users: [],
    stats: {
      total_users: 0,
      verified_users: 0,
      unverified_users: 0,
      total_books: 0,
      available_books: 0
    },
    isLoading: false,
    error: null as string | null
  }),

  actions: {
    async fetchDashboardStats() {
      this.isLoading = true;
      try {
        const response = await api.get('/admin/dashboard');
        this.stats = response.data;
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async fetchUsers() {
      this.isLoading = true;
      try {
        const response = await api.get('/admin/users');
        this.users = response.data.users;
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async deleteUser(id: number) {
      this.isLoading = true;
      try {
        await api.delete(`/admin/users/${id}`);
        await this.fetchUsers();
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },
  }
}); 