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
  userReservation?: any;
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

    async searchBooks(query: string) {
      this.isLoading = true;
      try {
        const response = await api.get('/books/search', { 
          params: { query } 
        });
        this.books = response.data.data || [];
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    }
  }
}); 