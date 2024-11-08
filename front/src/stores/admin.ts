import { defineStore } from 'pinia';
import { api } from '@/api';

export const useAdminStore = defineStore('admin', {
  state: () => ({
    users: [],
    books: [] as any[],
    pagination: {
      currentPage: 1,
      lastPage: 1,
      total: 0
    },
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

 

    async fetchBooks(params = {}) {
      this.isLoading = true;
      try {
        const searchParams = {
          search: params.search || '',
          category: params.category || '',
          page: params.page || 1,
          per_page: 10
        };

        const response = await api.get('/admin/books', { params: searchParams });
        
        this.books = response.data.data;
        this.pagination = {
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
          total: response.data.total
        };
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async fetchBookById(id) {
      console.log('Fetching book with ID:', id);
      try {
        const response = await api.get(`/admin/books/${id}`);
        console.log('Fetched book data:', response.data);
        return response.data;
      } catch (error) {
        console.log('Error fetching book by ID:', error);
        throw error;
      }
    },

    async createBook(bookData) {
      this.isLoading = true;
      try {
        const response = await api.post('/admin/books', bookData);
        await this.fetchBooks({ page: this.pagination.currentPage });
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async updateBook(id: number, bookData) {
      this.isLoading = true;
      try {
        const response = await api.put(`/admin/books/${id}`, bookData);
        await this.fetchBooks({ page: this.pagination.currentPage });
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async deleteBook(id: number) {
      this.isLoading = true;
      try {
        await api.delete(`/admin/books/${id}`);
        await this.fetchBooks({ page: this.pagination.currentPage });
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