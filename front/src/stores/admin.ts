import { defineStore } from 'pinia';
import { api } from '@/api';

export const useAdminStore = defineStore('admin', {
  state: () => ({
    users: [],
    books: [] as any[],
    reservations: [] as any[],
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

  getters: {
    activeReservations: (state) => 
      state.reservations.filter(r => ['pending', 'ready'].includes(r.status)),
    
    pastReservations: (state) => 
      state.reservations.filter(r => ['completed', 'cancelled'].includes(r.status))
  },

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

    async fetchAllReservations() {
      this.isLoading = true;
      try {
        const response = await api.get('/admin/reservations');
        this.reservations = response.data;
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

    async updateReservationStatus(id: number, status: string) {
      this.isLoading = true;
      try {
        const response = await api.put(`/admin/reservations/${id}/status`, { status });
        await this.fetchAllReservations();
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

    async fetchReservationHistory() {
      this.isLoading = true;
      try {
        const response = await api.get('/admin/reservations/history');
        this.reservationHistory = response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch reservation history';
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async cancelReservation(reservationId: number) {
      this.isLoading = true;
      try {
        const response = await api.post(`/admin/reservations/${reservationId}/cancel`);
        this.removeReservation(reservationId);
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to cancel reservation';
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async acceptReservation(reservationId: number) {
      this.isLoading = true;
      try {
        const response = await api.post(`/admin/reservations/${reservationId}/accept`);
        this.updateReservationStatus(reservationId, 'accepted');
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to accept reservation';
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    removeReservation(reservationId: number) {
      this.reservations = this.reservations.filter(reservation => reservation.id !== reservationId);
    },

    updateReservationStatus(reservationId: number, status: string) {
      const reservation = this.reservations.find(reservation => reservation.id === reservationId);
      if (reservation) {
        reservation.status = status;
      }
    },

    async fetchReservationStatistics() {
      this.isLoading = true;
      try {
        const response = await api.get('/admin/reservations/statistics');
        this.statistics = response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch reservation statistics';
        throw error;
      } finally {
        this.isLoading = false;
      }
    }
  },

  mutations: {
    SET_ACTIVE_RESERVATIONS(state, reservations) {
      state.activeReservations = reservations;
    },
    REMOVE_RESERVATION(state, reservationId) {
      state.activeReservations = state.activeReservations.filter(reservation => reservation.id !== reservationId);
    },
    UPDATE_RESERVATION_STATUS(state, { reservationId, status }) {
      const reservation = state.activeReservations.find(reservation => reservation.id === reservationId);
      if (reservation) {
        reservation.status = status;
      }
    }
  }
}); 