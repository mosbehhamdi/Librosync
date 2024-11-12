<template>
  <admin-layout>
    <ion-content class="ion-padding overflow-y-auto">
      <!-- Header with Title -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-center">Reservations Management</h1>
      </div>

      <!-- Horizontal Menu for Reservation Types -->
      <ion-segment v-model="selectedSegment" @ionChange="handleSegmentChange">
        <ion-segment-button value="active">Active Reservations</ion-segment-button>
        <ion-segment-button value="past">Past Reservations</ion-segment-button>
        <ion-segment-button value="history">Reservation History</ion-segment-button>
      </ion-segment>

      <!-- Search Bar and Filters -->
      <search-filter :initialSearch="filters.search" label="Search reservations..."
        @search="handleSearch" />
      <!-- 
    filter by status  
    -->
      <!-- Conditional Rendering Based on Selected Segment -->
      <div v-if="selectedSegment === 'active'">
        <ion-select v-model="selectedStatus" placeholder="Filter by Status" @ionChange="handleStatusChange">
          <ion-select-option value="pending">Pending</ion-select-option>
          <ion-select-option value="ready">Ready for Pickup</ion-select-option>
          <ion-select-option value="accepted">Accepted</ion-select-option>
        </ion-select>
        <ion-list>
          <ion-item-group>
            <ion-item-divider>
              <ion-label>Active Reservations</ion-label>
            </ion-item-divider>
            <ion-item v-for="reservation in activeReservations" :key="reservation.id">
              <ion-label>
                <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
                <p>Reserved by: {{ reservation.user ? reservation.user.name : 'Unknown User' }}</p>
                <p>
                  <ion-badge :color="getStatusColor(reservation.status)">
                    {{ getStatusText(reservation.status) }}
                  </ion-badge>
                  <span v-if="reservation.expiry" class="ml-2">Expires: {{ formatExpiry(reservation.expires_at) }}</span>
                  <span v-if="reservation.queue_position" class="ml-2">Queue Position: {{ reservation.queue_position }}</span>
                </p>
              </ion-label>
              <ion-button slot="end" fill="clear" color="danger"
                @click="showConfirmation('cancel', reservation)">Cancel</ion-button>
              <ion-button v-if="reservation.status === 'accepted'" slot="end" fill="clear" color="success"
                @click="showConfirmation('deliver', reservation)">Deliver</ion-button>
              <ion-button v-else-if="reservation.status === 'ready'" slot="end" fill="clear" color="success"
                @click="showConfirmation('accept', reservation)">Accept</ion-button>
            </ion-item>
          </ion-item-group>
        </ion-list>
      </div>

      <div v-else-if="selectedSegment === 'past'">
        <ion-select v-model="selectedStatus" placeholder="Filter by Status" @ionChange="handleStatusChange">
          <ion-select-option value="delivered">Delivered</ion-select-option>
          <ion-select-option value="cancelled">Cancelled</ion-select-option>
        </ion-select>
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
                  <ion-badge :color="getStatusColor(reservation.status)">
                    {{ getStatusText(reservation.status) }}
                  </ion-badge>
                  <span v-if="reservation.expiry" class="ml-2">Expires: {{ formatExpiry(reservation.expires_at) }}</span>
                  <span v-if="reservation.queue_position" class="ml-2">Queue Position: {{ reservation.queue_position }}</span>
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
import { onMounted, computed, ref } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useReservationStore } from '@/stores/reservation';
import { useBookStore } from '@/stores/book';
import { useAdminStore } from '@/stores/admin';
import { alertController } from '@ionic/vue';
import { format } from 'date-fns';
import SearchFilter from '@/components/admin/SearchFilter.vue'; // Adjust the path as necessary

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

const handleSegmentChange = (event: any) => {
  selectedSegment.value = event.detail.value;
};

const handleStatusChange = () => {
  const searchData = { status: selectedStatus.value };
  handleSearch(searchData);
};

const getStatusColor = (status: string) => {
  const colors = {
    pending: 'warning',
    ready: 'success',
    delivered: 'primary',
    cancelled: 'medium',
    accepted: 'tertiary'
  };
  return colors[status] || 'medium';
};

const getStatusText = (status: string) => {
  const texts = {
    pending: 'Waiting',
    ready: 'Ready for Pickup',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
    accepted: 'Accepted'
  };
  return texts[status] || status;
};

const formatExpiry = (date: string) => new Date(date).toLocaleDateString();
const formatDate = (date: string) => format(new Date(date), 'MMM dd, yyyy');

// Handle search input with debouncing
const handleSearch = async (filterData) => {
  if (!filterData) {
    console.error('filterData is undefined');
    return;
  }
  filters.value.search = filterData.search || '';
  filters.value.book = filterData.book || null;
  filters.value.user = filterData.user || null;
  filters.value.status = filterData.status || '';
  console.log('selected status : ' ,filters.value.status);
  reservationStore.pagination.currentPage = 1;
  await reservationStore.fetchAdminReservations({
    page: 1,
    search: filters.value.search,
    book_id: filters.value.book,
    user_id: filters.value.user,
    status: filters.value.status
  });
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

// Define the showConfirmation method
const showConfirmation = async (action: 'cancel' | 'accept' | 'deliver', reservation) => {
  const actionText = action.charAt(0).toUpperCase() + action.slice(1);
  const alert = await alertController.create({
    header: `${actionText} Reservation`,
    message: `Are you sure you want to ${action} this reservation?`,
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

// Define the adminReservationAction method
const adminReservationAction = (action: 'cancel' | 'accept' | 'deliver', reservation) => {
  reservationStore.adminReservationAction(action, reservation.id)
    .then(resp => {
      const index = reservationStore.adminRreservations.findIndex(r => r.id === reservation.id);
      if (index !== -1) {
        reservationStore.adminRreservations[index] = resp.reservation;
      }
    })
    .catch(error => {
      console.error('Error performing reservation action:', error);
    });
};


// Update the computed properties to filter by status
const activeReservations = computed(() => {
  return reservationStore.activeReservations.filter(reservation => 
    !selectedStatus.value || reservation.status === selectedStatus.value
  );
});

const pastReservations = computed(() => {
  return reservationStore.pastReservations.filter(reservation => 
    !selectedStatus.value || reservation.status === selectedStatus.value
  );
});




// Fetch reservations on mount
onMounted(() => {
  reservationStore.fetchAdminReservations({ page: 1 });
  bookStore.fetchAdminBooks();
  adminStore.fetchUsers();
  reservationStore.fetchReservationHistory(); // Fetch reservation history on mount
});
</script>
