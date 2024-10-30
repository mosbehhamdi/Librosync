import { defineStore } from 'pinia';
import { ref } from 'vue';
import { api } from '@/api';

export const useReservationStore = defineStore('reservation', {
  state: () => ({
    reservations: [],
    isLoading: false,
    error: null
  }),

  actions: {
    async fetchUserReservations() {
      this.isLoading = true;
      try {
        const response = await api.get('/user/reservations');
        this.reservations = response.data;
        return response.data;
      } catch (error) {
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async cancelReservation(id: number) {
      try {
        await api.post(`/user/reservations/${id}/cancel`);
        this.reservations = this.reservations.filter(r => r.id !== id);
      } catch (error) {
        this.error = error;
        throw error;
      }
    }
  }
}); 