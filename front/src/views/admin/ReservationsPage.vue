<template>
  <admin-layout>
    <ion-content class="ion-padding overflow-y-auto">
      <!-- Horizontal Menu for Reservation Types -->
      <ion-segment v-model="selectedSegment" @ionChange="handleSegmentChange">
        <ion-segment-button value="active">Active Reservations</ion-segment-button>
        <ion-segment-button value="past">Past Reservations</ion-segment-button>
        <ion-segment-button value="history">Reservation History</ion-segment-button>
      </ion-segment>

      <!-- Search Bar and Filters -->
      <ion-grid  v-if="['active','past'].includes(selectedSegment)">
        <ion-row>
          <ion-col size="6">
            <search-filter :initialSearch="filters.search" label="Search reservations..." @search="handleSearch" />
          </ion-col>

          <ion-card class="mb-5">
            <ion-card-content>
              <ion-item>
                <ion-col size="6" size-md="4" v-if="selectedSegment === 'active'">
                  <ion-select v-model="selectedStatus" placeholder="Filter by Status" @ionChange="handleStatusChange">
                    <ion-select-option value="pending">Pending</ion-select-option>
                    <ion-select-option value="ready">Ready</ion-select-option>
                    <ion-select-option value="accepted">Accepted</ion-select-option>
                    <ion-select-option value="delivered">Delivered</ion-select-option>
                  </ion-select>
                </ion-col>
                <ion-col size="6" v-else-if="selectedSegment === 'past'">
                  <ion-select v-model="selectedStatus" placeholder="Filter by Status" @ionChange="handleStatusChange">
                    <ion-select-option value="returned">Returned</ion-select-option>
                    <ion-select-option value="cancelled">Cancelled</ion-select-option>
                  </ion-select>
                </ion-col>
              </ion-item>

            </ion-card-content>
          </ion-card>
        </ion-row>
      </ion-grid>

      <!-- Conditional Rendering Based on Selected Segment -->
      <div v-if="selectedSegment === 'active'">

        <ion-list>
          <ion-item-group>
            <ion-item-divider>
              <ion-label>Active Reservations</ion-label>
            </ion-item-divider>
            <ion-item v-for="reservation in activeReservations" :key="reservation.id">
              <ion-label>
                <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
                <p>Reserved by: {{ reservation.user ? reservation.user.name : 'Unknown User' }}</p>
                <div class="reservation-details">
                  <ion-badge :color="getStatusColor(reservation.status)">
                    {{ getStatusText(reservation.status) }}
                  </ion-badge>
                  <QueuePositionDisplay
                    :position="reservation.queue_position"
                    :status="reservation.status"
                    :estimatedWaitTime="reservation.book?.waiting_time"
                  />
                  <span v-if="reservation.expires_at" class="ml-2">
                    Expires: {{ formatDate(reservation.expires_at) }}
                  </span>
                  <span v-if="reservation.due_date" class="ml-2">
                    Due: {{ formatDate(reservation.due_date) }}
                  </span>
                </div>
              </ion-label>
              <ion-button slot="end" fill="clear" color="danger"
                @click="showConfirmation('cancel', reservation)">Cancel</ion-button>
              <ion-button v-if="reservation.status === 'accepted'" slot="end" fill="clear" color="success"
                @click="showConfirmation('deliver', reservation)">Deliver</ion-button>
              <ion-button v-else-if="reservation.status === 'ready'" slot="end" fill="clear" color="success"
                @click="showConfirmation('accept', reservation)">Accept</ion-button>
              <ion-button v-if="reservation.status === 'delivered'" slot="end" fill="clear" color="success"
                @click="showConfirmation('return', reservation)">Return Book</ion-button>
            </ion-item>
          </ion-item-group>
        </ion-list>
      </div>

      <div v-else-if="selectedSegment === 'past'">
        <ion-list>
          <ion-item-group>
            <ion-item-divider>
              <ion-label>Past Reservations</ion-label>
            </ion-item-divider>
            <ion-item v-for="reservation in pastReservations" :key="reservation.id">
              <ion-label>
                <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
                <p>Reserved by: {{ reservation.user ? reservation.user.name : 'Unknown User' }}</p>
                <p>
                  <ion-badge :color="getStatusBadgeColor(reservation.status)">
                    {{ getStatusText(reservation.status) }}
                  </ion-badge>
                  <span v-if="reservation.expires_at" class="ml-2">
                    Expires: {{ formatDate(reservation.expires_at) }}
                  </span>
                  <span v-if="reservation.due_date" class="ml-2">
                    Due: {{ formatDate(reservation.due_date) }}
                  </span>
                  <span v-if="reservation.queue_position" class="ml-2">
                    Queue Position: {{ reservation.queue_position }}
                  </span>
                </p>
              </ion-label>
            </ion-item>
          </ion-item-group>
        </ion-list>
      </div>

      <div v-else-if="selectedSegment === 'history'">
        <ion-list>
          <ion-item-group>
            <ion-item-divider>
              <ion-label>Reservation History</ion-label>
            </ion-item-divider>
            <ion-item v-for="reservation in reservationStore.history" :key="reservation.id">
              <ion-label>
                <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
                <p>Reserved by: {{ reservation.user.name }}</p>
                <p>
                  <ion-badge :color="getStatusColor(reservation.status)">
                    {{ getStatusText(reservation.status) }}
                  </ion-badge>
                  <span class="ml-2">Reserved on: {{ formatDate(reservation.created_at) }}</span>
                </p>
              </ion-label>
            </ion-item>
          </ion-item-group>
        </ion-list>
      </div>

      <!-- Infinite Scroll for Reservations -->
      <ion-infinite-scroll
        v-if="!reservationStore.isLoading && reservationStore.pagination.currentPage < reservationStore.pagination.lastPage && (selectedSegment === 'active' || selectedSegment === 'past')"
        @ionInfinite="loadMoreReservations">
        <ion-infinite-scroll-content loading-spinner="bubbles"
          loading-text="Loading more reservations..."></ion-infinite-scroll-content>
      </ion-infinite-scroll>


    </ion-content>
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted, computed, ref, onUnmounted, watch } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useReservationStore } from '@/stores/reservation';
import { useBookStore } from '@/stores/book';
import { useAdminStore } from '@/stores/admin';
import { alertController, toastController } from '@ionic/vue';
import { format } from 'date-fns';
import SearchFilter from '@/components/admin/SearchFilter.vue'; // Adjust the path as necessary
import { wsService } from '@/services/websocket';
import QueuePositionDisplay from '@/components/QueuePositionDisplay.vue';

const reservationStore = useReservationStore();
const bookStore = useBookStore();
const adminStore = useAdminStore();

const filters = ref({
  search: '',
  book: null,
  user: null
});

const selectedSegment = ref('active'); // Default to active reservations
const selectedStatus = ref(''); // Default to show all statuses

const handleSegmentChange = async (event: any) => {
  selectedSegment.value = event.detail.value;
  selectedStatus.value = ''; // Reset status filter on segment change
  if (['active', 'past'].includes(event.detail.value)) {
    await refreshReservations(true);
  }
};

const handleStatusChange = async () => {
  await refreshReservations(true);
};

const getStatusColor = (status: string) => {
  const colors = {
    pending: 'warning',
    ready: 'success',
    delivered: 'primary',
    cancelled: 'medium',
    accepted: 'tertiary',
    delivered: 'secondary'
  };
  return colors[status] || 'medium';
};

const getStatusText = (status: string) => {
  const texts = {
    pending: 'Waiting',
    ready: 'Ready for Pickup',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
    accepted: 'Accepted',
    delivered: 'Currently Delivered'
  };
  return texts[status] || status;
};

const formatExpiry = (date: string) => new Date(date).toLocaleDateString();
const formatDate = (date: string) => format(new Date(date), 'MMM dd, yyyy');

const handleSearch = async (filterData) => {
  if (!filterData) return;
  
  filters.value = {
    search: filterData.search || '',
    book: filterData.book || null,
    user: filterData.user || null
  };
  
  await refreshReservations(true); // true indicates reset pagination
};

const loadMoreReservations = async (event: any) => {
  if (reservationStore.pagination.currentPage >= reservationStore.pagination.lastPage) {
    event.target.complete();
    return;
  }

  try {
    reservationStore.pagination.currentPage++;
    await reservationStore.fetchAdminReservations({
      page: reservationStore.pagination.currentPage,
      search: filters.value.search,
      book_id: filters.value.book,
      user_id: filters.value.user,
      status: filters.value.status
    });
  } catch (error) {
    console.error('Error loading more reservations:', error);
  } finally {
    event.target.complete();
  }
};

const showConfirmation = async (action: 'cancel' | 'accept' | 'deliver' | 'return', reservation) => {
  const actionText = action.charAt(0).toUpperCase() + action.slice(1);
  const alert = await alertController.create({
    header: `${actionText} Book`,
    message: `Are you sure you want to ${action} this book?`,
    buttons: [
      {
        text: 'No',
        role: 'cancel'
      },
      {
        text: 'Yes',
        role: 'confirm',
        handler: () => {
          adminReservationAction(action, reservation);
        }
      }
    ]
  });
  await alert.present();
};

const adminReservationAction = async (action: 'cancel' | 'accept' | 'deliver' | 'return', reservation) => {
  try {
    await reservationStore.adminReservationAction(action, reservation.id);
    
    const toast = await toastController.create({
      message: getActionSuccessMessage(action),
      duration: 2000,
      color: 'success',
      position: 'bottom'
    });
    await toast.present();

    // Immediate refresh after action
    await refreshReservations(false);
  } catch (error) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Action failed',
      duration: 3000,
      color: 'danger',
      position: 'bottom'
    });
    await toast.present();
  }
};

// Add helper function for success messages
const getActionSuccessMessage = (action: string) => {
  const messages = {
    cancel: 'Reservation cancelled successfully',
    accept: 'Reservation accepted successfully',
    deliver: 'Book delivered successfully',
    return: 'Book returned successfully'
  };
  return messages[action] || 'Action completed successfully';
};

// Add these computed properties for better reactivity
const currentFilters = computed(() => ({
  search: filters.value.search,
  status: selectedStatus.value,
  segment: selectedSegment.value
}));

// Update the activeReservations computed property
const activeReservations = computed(() => {
  return reservationStore.adminRreservations.filter(reservation => {
    const isActiveStatus = reservationStore.activeStatuses.includes(reservation.status);
    const matchesSelectedStatus = !selectedStatus.value || reservation.status === selectedStatus.value;
    return isActiveStatus && matchesSelectedStatus;
  });
});

const pastReservations = computed(() => {
  return reservationStore.adminRreservations.filter(reservation =>
    ['returned', 'cancelled'].includes(reservation.status) &&
    (!selectedStatus.value || reservation.status === selectedStatus.value)
  );
});

const getStatusBadgeColor = (status: string) => {
  const colors = {
    pending: 'warning',
    ready: 'success',
    accepted: 'tertiary',
    delivered: 'secondary',
    returned: 'primary',
    cancelled: 'medium'
  };
  return colors[status] || 'medium';
};

// Add a new refreshReservations function
const refreshReservations = async (resetPage = false) => {
  const page = resetPage ? 1 : reservationStore.pagination.currentPage;
  
  await reservationStore.fetchAdminReservations({
    page,
    search: filters.value.search,
    book_id: filters.value.book,
    user_id: filters.value.user,
    status: selectedStatus.value
  });
};

// Update mounted and unmounted hooks
onMounted(async () => {
  await refreshReservations(true);
  await bookStore.fetchAdminBooks();
  await adminStore.fetchUsers();
  await reservationStore.fetchReservationHistory();
  await reservationStore.initializeRealTimeUpdates();
});

onUnmounted(() => {
  reservationStore.stopAutoRefresh();
  wsService.close();
});

// Add this watch effect to handle segment changes
watch([selectedSegment, selectedStatus], async () => {
  await refreshReservations(true);
});
</script>
