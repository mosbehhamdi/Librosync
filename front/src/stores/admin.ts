import { defineStore } from 'pinia';
import { api } from '@/api';

interface Book {
  id: number;
  title: string;
  authors: string[];
  copies_count: number;
  available_copies: number;
  parts_count: number;
  publisher: string;
  edition_number: number;
  dewey_category: string;
  dewey_subcategory: string;
  price: number;
  comments: string;
  central_number: string;
  local_number: string;
  publication_date: string;
  acquisition_date: string;
}

export const useAdminStore = defineStore('admin', {
  state: () => ({
    users: [],
    books: [] as Book[],
    stats: {
      total_users: 0,
      verified_users: 0,
      unverified_users: 0,
      total_books: 0,
      available_books: 0
    },
    reservations: [] as any[],
    isLoading: false,
    error: null as string | null
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
    },

    async fetchBooks(params = {}) {
      this.isLoading = true;
      try {
        const response = await api.get('/books', { 
          params: {
            search: params.search || '',
            category: params.category || '',
            page: params.page || 1
          }
        });
        this.books = response.data.data;
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async createBook(bookData: Partial<Book>) {
      this.isLoading = true;
      try {
        const response = await api.post('/books', bookData);
        this.books.unshift(response.data);
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async updateBook(id: number, bookData: Partial<Book>) {
      this.isLoading = true;
      try {
        const response = await api.put(`/books/${id}`, bookData);
        const index = this.books.findIndex(book => book.id === id);
        if (index !== -1) {
          this.books[index] = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async deleteBook(id: number) {
      try {
        await api.delete(`/books/${id}`);
        this.books = this.books.filter(book => book.id !== id);
      } catch (error) {
        this.error = error;
        throw error;
      }
    },

    async updateBookCopies(id: number, copies: { copies_count: number, available_copies: number }) {
      try {
        const response = await api.put(`/books/${id}/copies`, copies);
        const index = this.books.findIndex(book => book.id === id);
        if (index !== -1) {
          this.books[index] = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      }
    },

    async searchBooks(query: string) {
      this.isLoading = true;
      try {
        const response = await api.get('/books/search', { 
          params: { query } 
        });
        this.books = response.data.data;
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async fetchAllReservations() {
      this.isLoading = true;
      try {
        const response = await api.get('/admin/reservations');
        this.reservations = response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error fetching reservations';
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async cancelReservation(id: number) {
      this.isLoading = true;
      try {
        await api.post(`/admin/reservations/${id}/cancel`);
        this.reservations = this.reservations.filter(r => r.id !== id);
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Error cancelling reservation';
        throw error;
      } finally {
        this.isLoading = false;
      }
    }
  }
}); 