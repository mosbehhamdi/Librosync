import { defineStore } from 'pinia';
import { api } from '@/api';
import { wsService } from '@/services/websocket';

// Enhanced Reservation interface
interface Reservation {
  id: number;
  user_id: number;
  book_id: number;
  status: string;
  queue_position?: number;
  expires_at?: string;
  delivered_at?: string;
  due_date?: string;
  created_at: string;
  updated_at: string;
  book?: {
    id: number;
    title: string;
    authors: string[];
    available_copies: number;
  };
  user?: {
    id: number;
    name: string;
  };
}

interface PaginationState {
  currentPage: number;
  lastPage: number;
  total: number;
  perPage: number;
}

interface QueueUpdate {
  bookId: number;
  positions: Array<{
    reservationId: number;
    position: number;
  }>;
}

export const useReservationStore = defineStore('reservation', {
  state: () => ({
    history: [] as Reservation[],
    statistics: null,
    isLoading: false,
    error: null,
    reservations: [] as Reservation[],
    adminRreservations: [] as Reservation[],
    pagination: {
      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: 15
    } as PaginationState,
    activeStatuses: ['pending', 'ready', 'accepted', 'delivered'],
    pastStatuses: ['returned', 'cancelled'],
    refreshInterval: null as any,
    lastRefreshTime: null as Date | null,
    currentFilters: {
      search: '',
      book_id: null,
      user_id: null,
      status: null
    },
    reservationHistory: [] as Reservation[],
    historyPagination: {
      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: 15
    } as PaginationState,
  }),

  getters: {
    activeReservations: (state) => 
      Array.isArray(state.reservations) 
        ? state.reservations.filter(r => state.activeStatuses.includes(r.status))
        : [],
    
        deliveredReservations: (state) =>
      Array.isArray(state.reservations)
        ? state.reservations.filter(r => r.status === 'delivered')
        : [],
      
    pastReservations: (state) =>
      Array.isArray(state.reservations)
        ? state.reservations.filter(r => state.pastStatuses.includes(r.status))
        : [],
    returnedReservations: (state) =>
      Array.isArray(state.reservations)
        ? state.reservations.filter(r => r.status === 'returned')
        : [],
    statusText: (state) => (status: string) => {
      const texts = {
        pending: 'Waiting',
        ready: 'Ready for Pickup',
        accepted: 'Accepted',
        delivered: 'Delivered to User',
        returned: 'Returned to Library',
        cancelled: 'Cancelled'
      };
      return texts[status] || status;
    },

    statusColor: (state) => (status: string) => {
      const colors = {
        pending: 'warning',
        ready: 'success',
        accepted: 'tertiary',
        delivered: 'secondary',
        returned: 'primary',
        cancelled: 'medium'
      };
      return colors[status] || 'medium';
    }
  },

  actions: {
    async fetchUserReservations() {
      return this.handleApiCall(async () => {
        const response = await api.get('/reservations');
        console.log('User reservations response:', response.data);
        
        // Ensure we're getting all reservations including history
        this.reservations = Array.isArray(response.data.data) 
          ? response.data.data 
          : [];

        this.history = this.reservations.filter(r => 
          ['returned', 'cancelled'].includes(r.status)
        );

        return this.reservations;
      });
    },

    async fetchAdminReservations({ 
        page = 1, 
        search = '', 
        book_id = null, 
        user_id = null, 
        status = null, 
        type = 'current' 
    }) {
        return this.handleApiCall(async () => {
            const params = {
                page,
                search,
                book_id,
                user_id,
                status,
                type,
                per_page: this.pagination.perPage
            };

            // Remove null/undefined values
            Object.keys(params).forEach(key => 
                params[key] === null && delete params[key]
            );

            const response = await api.get('/reservations', { params });
            
            if (page === 1) {
                this.adminRreservations = response.data.data;
            } else {
                // Append new reservations without sorting
                this.adminRreservations = [...this.adminRreservations, ...response.data.data];
            }
            
            this.pagination = {
                currentPage: response.data.current_page,
                lastPage: response.data.last_page,
                total: response.data.total,
                perPage: response.data.per_page
            };
        
            return this.adminRreservations;
        });
    },

    async adminReservationAction(action: 'cancel' | 'accept' | 'deliver' | 'return', reservationId: number) {
      if (!reservationId) {
        console.error('Invalid reservation ID:', reservationId);
        throw new Error('Invalid reservation ID');
      }

      this.isLoading = true;
      try {
        const response = await api.post(`/admin/reservations/${reservationId}/${action}`);
        
        // Add delay before refreshing
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Update local state
        const updatedReservation = response.data.reservation;
        if (updatedReservation && typeof updatedReservation.id !== 'undefined') {
          // Ensure adminRreservations is initialized
          if (!Array.isArray(this.adminRreservations)) {
            this.adminRreservations = [];
          }

          const index = this.adminRreservations.findIndex(r => r && r.id === reservationId);
          if (index !== -1) {
            this.adminRreservations[index] = updatedReservation;
          }
        }

        return response.data;
      } catch (error) {
        console.error('Reservation action failed:', error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async userReservationAction(action: 'cancel' | 'reserve' | 'joinWaitlist', reservationId?: number, bookId?: number) {
      this.isLoading = true;
      try {
        // Check limits for reserve and waitlist actions
        if (action === 'reserve' || action === 'joinWaitlist') {
          const statsResponse = await api.get('/user/reservation-stats');
          if (statsResponse.data.delivered_count >= statsResponse.data.max_allowed) {
            throw new Error(`Maximum number of books (${statsResponse.data.max_allowed}) reached`);
          }
          if (statsResponse.data.has_overdue) {
            throw new Error('Cannot reserve books while you have overdue books');
          }
        }

        let response;
        if (action === 'cancel' && reservationId) {
          response = await api.post(`/reservations/${reservationId}/cancel`);
        } else if (action === 'reserve' && bookId) {
          response = await api.post(`/books/${bookId}/reserve`);
        } else if (action === 'joinWaitlist' && bookId) {
          response = await api.post(`/books/${bookId}/waitlist`);
        }

        // Add delay before refreshing
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Refresh reservations
        await this.fetchUserReservations();
        
        return {
          reservation: action === 'cancel' ? null : response?.data.reservation,
          message: action === 'cancel' ? 'Reservation cancelled successfully' : 'Reservation successful'
        };
      } catch (error) {
        console.error('User reservation action failed:', error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async fetchReservationHistory({ 
      page = 1, 
      search = '', 
      book_id = null, 
      user_id = null 
    } = {}) {
      try {
        this.isLoading = true;
        console.log('Fetching history with params:', { page, search, book_id, user_id });
        
        const params = {
          page,
          search,
          book_id,
          user_id,
          per_page: this.historyPagination.perPage
        };

        // Remove null/undefined values
        Object.keys(params).forEach(key => 
          params[key] === null && delete params[key]
        );

        const response = await api.get('/reservations/history', { params });
        console.log('History response:', response.data);

        if (!response.data || !Array.isArray(response.data.data)) {
          console.error('Invalid response format:', response.data);
          throw new Error('Invalid response format');
        }
        
        if (page === 1) {
          this.reservationHistory = response.data.data;
        } else {
          this.reservationHistory = [...this.reservationHistory, ...response.data.data];
        }

        this.historyPagination = {
          currentPage: response.data.current_page || 1,
          lastPage: response.data.last_page || 1,
          total: response.data.total || 0,
          perPage: response.data.per_page || 15
        };

        return this.reservationHistory;
      } catch (error) {
        console.error('Error fetching reservation history:', error);
        this.error = error;
        throw error;
      } finally {
        this.isLoading = false;
      }
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

    async getQueuePosition(bookId: number): Promise<number | null> {
      try {
        const response = await api.get(`/books/${bookId}/queue-position`);
        return response.data.queue_position;
      } catch (error) {
        console.error('Error fetching queue position:', error);
        return null;
      }
    },

    async handleApiCall<T>(apiCall: () => Promise<T>): Promise<T> {
      this.isLoading = true;
      this.error = null;
      
      try {
        const result = await apiCall();
        return result;
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'An error occurred';
        console.error('API Call Error:', this.error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async initializeRealTimeUpdates() {
      wsService.subscribe('reservations', (data) => {
        if (data.type === 'fallback_activated') {
          console.warn('WebSocket not available, using polling');
          this.startPolling();
          return;
        }

        const reservation = data.reservation || data;
        
        // Handle the update through our centralized method
        this.handleReservationUpdate(reservation);
      });

      if (wsService.isFallbackMode()) {
        this.startPolling();
      }
    },

    startPolling(interval = 3000) {
      this.stopAutoRefresh();

      const pollFunction = async () => {
        if (this.isLoading) return;
        
        try {
          const response = await api.get('/reservations', {
            params: {
              page: 1,
              search: this.currentFilters?.search || '',
              book_id: this.currentFilters?.book_id,
              user_id: this.currentFilters?.user_id,
              status: this.currentFilters?.status,
              type: 'current',
              per_page: this.pagination.perPage
            }
          });

          // Ensure adminRreservations is initialized
          if (!Array.isArray(this.adminRreservations)) {
            this.adminRreservations = [];
          }

          // Get the new reservations
          const newReservations = response.data.data || [];

          // Update existing reservations and add new ones
          newReservations.forEach(newRes => {
            if (!newRes || typeof newRes.id === 'undefined') {
              console.warn('Invalid reservation object received:', newRes);
              return;
            }

            const existingIndex = this.adminRreservations.findIndex(r => r && r.id === newRes.id);
            if (existingIndex !== -1) {
              // Update existing reservation
              this.adminRreservations[existingIndex] = newRes;
            } else {
              // Add new reservation at the beginning
              this.adminRreservations.unshift(newRes);
            }
          });

          // Update pagination info but keep current page
          this.pagination = {
            ...this.pagination,
            total: response.data.total,
            lastPage: response.data.last_page,
            perPage: response.data.per_page
          };
        } catch (error) {
          console.error('Polling failed:', error);
        }
      };

      // Store the interval ID for cleanup
      this.refreshInterval = setInterval(pollFunction, interval);
      // Initial poll
      pollFunction();
    },

    handleReservationUpdate(updatedReservation: Reservation) {
      if (!updatedReservation || !updatedReservation.status) {
        console.warn('Invalid reservation update received:', updatedReservation);
        return;
      }

      // Ensure adminRreservations is initialized
      if (!Array.isArray(this.adminRreservations)) {
        this.adminRreservations = [];
      }

      // Find the index of the existing reservation
      const existingIndex = this.adminRreservations.findIndex(
        r => r && r.id === updatedReservation.id
      );

      if (existingIndex !== -1) {
        // Update the reservation in place to maintain order
        this.adminRreservations[existingIndex] = updatedReservation;
      } else {
        // If it's a new reservation, add it at the beginning
        this.adminRreservations.unshift(updatedReservation);
      }

      // If queue position changed, update all positions for the book
      if (updatedReservation.queue_position !== undefined) {
        this.updateQueuePositions(updatedReservation.book_id);
      }
    },

    resetState() {
      this.reservations = [];
      this.adminRreservations = [];
      this.history = [];
      this.expiredReservations = [];
      this.statistics = null;
      this.error = null;
      this.pagination = {
        currentPage: 1,
        lastPage: 1,
        total: 0,
        perPage: 15
      };
      this.reservationHistory = [];
      this.historyPagination = {
        currentPage: 1,
        lastPage: 1,
        total: 0,
        perPage: 15
      };
      this.stopAutoRefresh();
    },

    stopAutoRefresh() {
      if (this.refreshInterval) {
        clearInterval(this.refreshInterval);
        this.refreshInterval = null;
      }
    },

    async updateQueuePositions(bookId: number): Promise<void> {
      try {
        const response = await api.get(`/books/${bookId}/queue-positions`);
        const updates = response.data as QueueUpdate;
        
        // Update local state
        this.adminRreservations = this.adminRreservations.map(reservation => {
          const update = updates.positions.find(p => p.reservationId === reservation.id);
          if (update) {
            return { ...reservation, queue_position: update.position };
          }
          return reservation;
        });
      } catch (error) {
        console.error('Error updating queue positions:', error);
      }
    }
  }
});