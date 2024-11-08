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

  }),
  getters: {
    activeReservations: (state) => 
      state.reservations.filter(r => ['pending', 'ready'].includes(r.status)),
    
    pastReservations: (state) => 
      state.reservations.filter(r => ['delivered', 'cancelled'].includes(r.status))
  },

  actions: {
    async fetchUserReservations() {
      this.isLoading = true;
      try {
        const response = await api.get('/reservations');
        this.reservations = response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch reservations';
        console.error('Error fetching user reservations:', this.error);
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
    async updateReservationStatus(id: number, status: string) {
      this.isLoading = true;
      try {
        const response = await api.put(`/admin/reservations/${id}/status`, { status });
        const reservation = this.reservations.find(r => r.id === id);
        if (reservation) {
          reservation.status = status;
        }
        return response.data;
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
    },

    async adminReservationAction(action: 'cancel' | 'accept' | 'deliver', reservationId: number) {
      this.isLoading = true;
      try {
        let response;
        if (action === 'cancel') {
          response = await api.post(`/admin/reservations/${reservationId}/cancel`);
        } else if (action === 'accept') {
          response = await api.post(`/admin/reservations/${reservationId}/accept`);
        } else if (action === 'deliver') {
          response = await api.post(`/admin/reservations/${reservationId}/deliver`);
        }
        
        const updatedReservation = response.data;
        const reservationIndex = this.reservations.findIndex(r => r.id === reservationId);
        if (reservationIndex !== -1) {
          this.reservations[reservationIndex] = updatedReservation;
        }
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || `Failed to ${action} reservation`;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async reserveBook(bookId: number) {
      this.isLoading = true;
      console.log(`Reserving book with ID: ${bookId}...`);
      try {
        const response = await api.post(`/books/${bookId}/reserve`);
        console.log('Book reserved successfully:', response.data);
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to reserve book';
        console.error('Error reserving book:', this.error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async cancelReservation(reservationId: number) {
      console.log(`Cancelling reservation with ID: ${reservationId}...`);
      try {
        const response = await api.post(`/reservations/${reservationId}/cancel`);
        console.log('Reservation cancelled successfully:', response.data);
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to cancel reservation';
        console.error('Error cancelling reservation:', this.error);
        throw error;
      }
    },

    async joinWaitlist(bookId: number) {
      this.isLoading = true;
      console.log(`Joining waitlist for book with ID: ${bookId}...`);
      try {
        const response = await api.post(`/books/${bookId}/waitlist`);
        console.log('Joined waitlist successfully:', response.data);
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to join waitlist';
        console.error('Error joining waitlist:', this.error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async getReservationHistory() {
      this.isLoading = true;
      console.log('Fetching reservation history...');
      try {
        const response = await api.get('/reservations/history');
        this.history = response.data;
        console.log('Reservation history fetched successfully:', this.history);
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch history';
        console.error('Error fetching reservation history:', this.error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async getReservationStatistics() {
      console.log('Fetching reservation statistics...');
      try {
        const response = await api.get('/reservations/statistics');
        this.statistics = response.data;
        console.log('Reservation statistics fetched successfully:', this.statistics);
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch statistics';
        console.error('Error fetching reservation statistics:', this.error);
        throw error;
      }
    },


    async fetchExpiredReservations() {
      this.isLoading = true;
      try {
        const response = await api.get('/reservations/expired');
        this.expiredReservations = response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch expired reservations';
        console.error('Error fetching expired reservations:', this.error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async getQueuePosition(bookId: number) {
      console.log(`Getting queue position for book with ID: ${bookId}...`);
      try {
        const response = await api.get(`/books/${bookId}/queue-position`);
        console.log('Queue position retrieved successfully:', response.data.queue_position);
        return response.data.queue_position;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to get queue position';
        console.error('Error getting queue position:', this.error);
        throw error;
      }
    },

   /* async fetchUserReservationByBookId(bookId: number) {
      console.log(`Fetching reservation for book with ID: ${bookId}...`);
      try {
        const response = await api.get(`/reservations/book/${bookId}`);
        console.log('Reservation fetched successfully:', response.data);
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch reservation';
        console.error('Error fetching reservation:', this.error);
        throw error;
      }
    } */
  }
}); 