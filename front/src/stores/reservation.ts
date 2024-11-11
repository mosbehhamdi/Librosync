import { defineStore } from 'pinia';
import { api } from '@/api';

export const useReservationStore = defineStore('reservation', {
  state: () => ({
    history: [],
    statistics: null,
    isLoading: false,
    error: null,
    expiredReservations: [],
    reservations: [] as any[],
    adminRreservations: [],
    pagination: {
      currentPage: 1,
      lastPage: 1,
      total: 0
    },
  }),
  getters: {
    activeReservations: (state) => 
      state.adminRreservations.filter(r => ['pending', 'ready','accepted'].includes(r.status)),
    
    pastReservations: (state) => 
      state.adminRreservations.filter(r => ['delivered', 'cancelled'].includes(r.status))
  },

  actions: {
    async fetchUserReservations() {
      return this.handleApiCall(async () => {
        const response = await api.get('/reservations');
        this.reservations = response.data;
      });
    },
    async fetchAdminReservations() {
      this.isLoading = true;
      try {
        const response = await api.get('/reservations', {
          params: { 
            page: this.pagination.currentPage,
            per_page: 100
          }
        });
        this.adminRreservations = response.data.data;
        console.log('Fetched Reservations:', this.adminRreservations);
        this.pagination.total = response.data.total;
        this.pagination.lastPage = response.data.last_page;
      } catch (error) {
        this.error = error;
        console.error('Error fetching reservations:', error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async adminReservationAction(action: 'cancel' | 'accept' | 'deliver', reservationId: number) {
      return this.handleApiCall(async () => {
        let response;
        if (action === 'cancel') {
          response = await api.post(`/admin/reservations/${reservationId}/cancel`);
        } else if (action === 'accept') {
          response = await api.post(`/admin/reservations/${reservationId}/accept`);
        } else if (action === 'deliver') {
          response = await api.post(`/admin/reservations/${reservationId}/deliver`);
        }
        
        const updatedReservation = response.data;
        const reservationIndex = this.adminRreservations.findIndex(r => r.id === reservationId);
        if (reservationIndex !== -1) {
          this.adminRreservations[reservationIndex] = updatedReservation;
        }
        return response.data;
      });
    },

    async userReservationAction(action: 'cancel' | 'reserve' | 'joinWaitlist', reservationId?: number, bookId?: number) {
      return this.handleApiCall(async () => {
        let response;
        if (action === 'cancel' && reservationId) {
          response = await api.post(`/reservations/${reservationId}/cancel`);
        } else if (action === 'reserve' && bookId) {
          response = await api.post(`/books/${bookId}/reserve`);
        } else if (action === 'joinWaitlist' && bookId) {
          response = await api.post(`/books/${bookId}/waitlist`);
        }
        return response.data;
      });
    },

    async fetchReservationHistory() {
      return this.handleApiCall(async () => {
        const response = await api.get('/reservations/history');
        this.history = response.data;
      });
    },

    async fetchReservationStatistics() {
      return this.handleApiCall(async () => {
        const response = await api.get('/admin/reservations/statistics');
        this.statistics = response.data;
      });
    },

    async fetchExpiredReservations() {
      return this.handleApiCall(async () => {
        const response = await api.get('/reservations/expired');
        this.expiredReservations = response.data;
      });
    },

    async getQueuePosition(bookId: number) {
      return this.handleApiCall(async () => {
        const response = await api.get(`/books/${bookId}/queue-position`);
        return response.data.queue_position;
      });
    },

    async handleApiCall(apiCall: () => Promise<any>) {
      this.isLoading = true;
      this.error = null; // Reset error before the call
      try {
        return await apiCall();
      } catch (error: any) {
        this.error = error.response?.data?.message || 'An error occurred';
        console.error(this.error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async fetchMoreReservations() {
      if (this.pagination.currentPage >= this.pagination.lastPage) return; // Prevent loading if on the last page

      this.isLoading = true;
      try {
        this.pagination.currentPage++; // Increment the current page
        const response = await api.get('/reservations', {
          params: { 
            page: this.pagination.currentPage,
            per_page: 15 // Ensure this matches the API's expected per_page
          }
        });
        this.adminRreservations.push(...response.data.data); // Append new reservations
        this.pagination.total = response.data.total; // Update total count
        this.pagination.lastPage = response.data.last_page; // Update last page number
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },
  }
});