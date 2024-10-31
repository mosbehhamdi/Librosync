import { defineStore } from 'pinia';
import { ref } from 'vue';
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

interface BookFilters {
  search?: string;
  category?: string;
}

export const useBookStore = defineStore('book', {
  state: () => ({
    books: [] as Book[],
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

    // Add new method for fetching a single book
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
    }
  }
});