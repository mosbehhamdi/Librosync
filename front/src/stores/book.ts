import { defineStore } from 'pinia';
import { api } from '@/api';

interface Book {
  id: number;
  title: string;
  authors: string[];
  isbn: string;
  dewey_code: string;
  copies_count: number;
  available_copies: number;
  waiting_time?: number;
  userReservation?: {
    id: number;
    status: string;
    queue_position: number;
  };
}

export const useBookStore = defineStore('book', {
  state: () => ({
    pagination: {
      currentPage: 1,
      lastPage: 1,
      total: 0
    },
    books: [] as Book[],
    adminBooks: [] as Book[],
    isLoading: false,
    error: null as string | null
  }),

  actions: {
    async fetchBooks(params = {}) {
      this.isLoading = true;
      try {
        const response = await api.get('/books', { 
          params: {
            search: params.search || '',
            category: params.category || '',
            page: params.page || 1,
            per_page: 10
          }
        });
        this.books = response.data.data || [];
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async searchBooks(params: { query?: string; category?: string; page?: number }) {
      this.isLoading = true;
      console.log('BookStore searchBooks called with params:', params);
      
      try {
        const response = await api.get('/books', { 
          params: {
            search: params.query,
            category: params.category,
            page: params.page || 1,
            per_page: 10
          }
        });
        
        console.log('Search response:', response.data);
        this.books = response.data.data || [];
        return response.data;
      } catch (error) {
        console.error('Search error:', error);
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async getBook(id: number) {
      try {
        const response = await api.get(`/books/${id}`);
        const index = this.books.findIndex(b => b.id === id);
        if (index !== -1) {
          this.books[index] = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      }
    },

    async adminBookAction(action: 'create' | 'update' | 'delete', bookData?: Book, id?: number) {
      this.isLoading = true;
      try {
        let response;
        if (action === 'create' && bookData) {
          response = await api.post('/books', bookData);
        } else if (action === 'update' && id && bookData) {
          response = await api.put(`/admin/books/${id}`, bookData);
        } else if (action === 'delete' && id) {
          await api.delete(`/admin/books/${id}`);
        }

        if (action !== 'delete') {
          await this.fetchAdminBooks({ page: 1 }); // Fetch admin books after create/update
        } else {
          await this.fetchAdminBooks({ page: 1 }); // Fetch admin books after deletion
        }

        return response?.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async fetchAdminBooks(params = {}) {
      this.isLoading = true;
      try {
        const response = await api.get('/books', { 
          params: {
            search: params.search || '',
            category: params.category || '',
            page: params.page || 1,
            per_page: 10
          }
        });

        if (params.page === 1) {
          this.adminBooks = response.data.data;
        } else {
          this.adminBooks.push(...response.data.data);
        }

        this.pagination.currentPage = response.data.current_page;
        this.pagination.lastPage = response.data.last_page;
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },
  }
});