import { defineStore } from 'pinia';
import { api } from '@/api';

export const useReservationStore = defineStore('reservation', {
  state: () => ({
    reservations: [],
    history: [],
    statistics: null,
    isLoading: false,
    error: null,
    expiredReservations: []
  }),

  actions: {
    async fetchUserReservations() {
      this.isLoading = true;
      try {
        const response = await api.get('/reservations');
        this.reservations = response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch reservations';
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async reserveBook(bookId: number) {
      this.isLoading = true;
      try {
        const response = await api.post(`/books/${bookId}/reserve`);
        await this.fetchUserReservations(); // Refresh reservations after new reservation
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to reserve book';
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async cancelReservation(reservationId: number) {
      try {
        const response = await api.post(`/reservations/${reservationId}/cancel`);
        await this.fetchUserReservations(); // Refresh reservations after cancellation
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to cancel reservation';
        throw error;
      }
    },

    async getReservationHistory() {
      this.isLoading = true;
      try {
        const response = await api.get('/reservations/history');
        this.history = response.data;
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch history';
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async getReservationStatistics() {
      try {
        const response = await api.get('/reservations/statistics');
        this.statistics = response.data;
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch statistics';
        throw error;
      }
    },

    async joinWaitlist(bookId: number) {
      this.isLoading = true;
      try {
        const response = await api.post(`/books/${bookId}/waitlist`);
        await this.fetchUserReservations(); // Refresh reservations after joining waitlist
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to join waitlist';
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async fetchExpiredReservations() {
      this.isLoading = true;
      try {
        const response = await api.get('/reservations/expired');
        this.expiredReservations = response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch expired reservations';
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async getQueuePosition(bookId: number) {
      try {
        const response = await api.get(`/books/${bookId}/queue-position`);
        return response.data.queue_position;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to get queue position';
        throw error;
      }
    }
  }
}); 