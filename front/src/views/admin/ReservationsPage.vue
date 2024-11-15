<template>
  <ion-content class="ion-padding overflow-y-auto">
    <ion-segment v-model="selectedSegment" @ionChange="handleSegmentChange">
      <ion-segment-button value="active">{{ t('admin.reservations.activeTitle') }}</ion-segment-button>
      <ion-segment-button value="past">{{ t('admin.reservations.pastTitle') }}</ion-segment-button>
      <ion-segment-button value="history">{{ t('admin.reservations.historyTitle') }}</ion-segment-button>
    </ion-segment>

    <ion-grid v-if="['active','past'].includes(selectedSegment)">
      <ion-row>
        <ion-col size="6">
          <search-filter 
            :initialSearch="filters.search" 
            :label="t('admin.filters.searchReservations')" 
            @search="handleSearch" 
          />
        </ion-col>

        <ion-card class="mb-5">
          <ion-card-content>
            <ion-item>
              <ion-col size="6" size-md="4" v-if="selectedSegment === 'active'">
                <ion-select v-model="selectedStatus" :placeholder="t('admin.filters.filterByStatus')" @ionChange="handleStatusChange">
                  <ion-select-option value="pending">{{ t('reservations.status.pending') }}</ion-select-option>
                  <ion-select-option value="ready">{{ t('reservations.status.ready') }}</ion-select-option>
                  <ion-select-option value="accepted">{{ t('reservations.status.accepted') }}</ion-select-option>
                  <ion-select-option value="delivered">{{ t('reservations.status.delivered') }}</ion-select-option>
                </ion-select>
              </ion-col>
              <ion-col size="6" v-else-if="selectedSegment === 'past'">
                <ion-select v-model="selectedStatus" :placeholder="t('admin.filters.filterByStatus')" @ionChange="handleStatusChange">
                  <ion-select-option value="returned">{{ t('reservations.status.returned') }}</ion-select-option>
                  <ion-select-option value="cancelled">{{ t('reservations.status.cancelled') }}</ion-select-option>
                </ion-select>
              </ion-col>
            </ion-item>
          </ion-card-content>
        </ion-card>
      </ion-row>
    </ion-grid>

    <div v-if="selectedSegment === 'active'">
      <ion-list>
        <ion-item-group>
          <ion-item-divider>
            <ion-label>{{ t('admin.reservations.activeTitle') }}</ion-label>
          </ion-item-divider>
          <ion-item v-for="reservation in activeReservations" :key="reservation.id">
            <ion-label>
              <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
              <p>{{ t('reservations.reservedBy') }}: {{ reservation.user ? reservation.user.name : t('common.unknownUser') }}</p>
              <div class="reservation-details">
                <ion-badge :color="getStatusColor(reservation.status)" class="status-badge">
                  <ion-icon :icon="getStatusIcon(reservation.status)" class="status-icon"></ion-icon>
                  {{ getStatusText(reservation.status) }}
                </ion-badge>
                <QueuePositionDisplay
                  :position="reservation.queue_position"
                  :status="reservation.status"
                  :estimatedWaitTime="reservation.book?.waiting_time"
                />
                <span v-if="reservation.expires_at" class="ml-2">
                  {{ t('reservations.expires') }}: {{ formatDate(reservation.expires_at) }}
                </span>
                <span v-if="reservation.due_date" class="ml-2">
                  {{ t('reservations.due') }}: {{ formatDate(reservation.due_date) }}
                </span>
              </div>
            </ion-label>
            <ion-button slot="end" fill="clear" color="danger"
              @click="showConfirmation('cancel', reservation)">{{ t('common.actions.cancel') }}</ion-button>
            <ion-button v-if="reservation.status === 'accepted'" slot="end" fill="clear" color="success"
              @click="showConfirmation('deliver', reservation)">{{ t('reservations.actions.deliver') }}</ion-button>
            <ion-button v-else-if="reservation.status === 'ready'" slot="end" fill="clear" color="success"
              @click="showConfirmation('accept', reservation)">{{ t('reservations.actions.accept') }}</ion-button>
            <ion-button v-if="reservation.status === 'delivered'" slot="end" fill="clear" color="success"
              @click="showConfirmation('return', reservation)">{{ t('reservations.actions.return') }}</ion-button>
          </ion-item>
        </ion-item-group>
      </ion-list>
    </div>

    <div v-else-if="selectedSegment === 'past'">
      <ion-list>
        <ion-item-group>
          <ion-item-divider>
            <ion-label>{{ t('admin.reservations.pastTitle') }}</ion-label>
          </ion-item-divider>
          <ion-item v-for="reservation in pastReservations" :key="reservation.id">
            <ion-label>
              <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
              <p>{{ t('reservations.reservedBy') }}: {{ reservation.user ? reservation.user.name : t('common.unknownUser') }}</p>
              <p>
                <ion-badge :color="getStatusBadgeColor(reservation.status)" class="status-badge">
                  <ion-icon :icon="getStatusIcon(reservation.status)" class="status-icon"></ion-icon>
                  {{ getStatusText(reservation.status) }}
                </ion-badge>
                <span v-if="reservation.expires_at" class="ml-2">
                  {{ t('reservations.expires') }}: {{ formatDate(reservation.expires_at) }}
                </span>
                <span v-if="reservation.due_date" class="ml-2">
                  {{ t('reservations.due') }}: {{ formatDate(reservation.due_date) }}
                </span>
                <span v-if="reservation.queue_position" class="ml-2">
                  {{ t('reservations.queuePosition') }}: {{ reservation.queue_position }}
                </span>
              </p>
            </ion-label>
          </ion-item>
        </ion-item-group>
      </ion-list>
    </div>

    <div v-else-if="selectedSegment === 'history'">
      <ion-grid>
        <ion-row>
          <ion-col size="6">
            <search-filter 
              :initialSearch="filters.search" 
              :label="t('admin.reservations.filters.searchReservations')" 
              @search="handleSearch" 
            />
          </ion-col>
        </ion-row>
      </ion-grid>

      <ion-list>
        <ion-item-group>
          <ion-item-divider>
            <ion-label>{{ t('admin.reservations.title') }}</ion-label>
          </ion-item-divider>
          <template v-if="Array.isArray(reservationStore.reservationHistory)">
            <ion-item v-for="reservation in reservationStore.reservationHistory" :key="reservation.id">
              <ion-label>
                <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
                <p>{{ t('reservations.reservedBy') }}: {{ reservation.user?.name || t('common.unknownUser') }}</p>
                <div class="reservation-history">
                  <!-- Initial reservation -->
                  <div class="history-item">
                    <ion-badge :color="getStatusColor('pending')" class="status-badge">
                      <ion-icon :icon="getStatusIcon('pending')" class="status-icon"></ion-icon>
                      {{ getStatusText('pending') }}
                    </ion-badge>
                    <span class="date-text">{{ formatDate(reservation.created_at) }}</span>
                  </div>
                  
                  <!-- Ready for pickup -->
                  <div v-if="reservation.status_history.ready" class="history-item">
                    <ion-badge :color="getStatusColor('ready')" class="status-badge">
                      <ion-icon :icon="getStatusIcon('ready')" class="status-icon"></ion-icon>
                      {{ getStatusText('ready') }}
                    </ion-badge>
                    <span class="date-text">{{ formatDate(reservation.status_history.ready) }}</span>
                  </div>
                  
                  <!-- Accepted -->
                  <div v-if="reservation.status_history.accepted" class="history-item">
                    <ion-badge :color="getStatusColor('accepted')" class="status-badge">
                      <ion-icon :icon="getStatusIcon('accepted')" class="status-icon"></ion-icon>
                      {{ getStatusText('accepted') }}
                    </ion-badge>
                    <span class="date-text">{{ formatDate(reservation.status_history.accepted) }}</span>
                  </div>
                  
                  <!-- Delivered -->
                  <div v-if="reservation.status_history.delivered" class="history-item">
                    <ion-badge :color="getStatusColor('delivered')" class="status-badge">
                      <ion-icon :icon="getStatusIcon('delivered')" class="status-icon"></ion-icon>
                      {{ getStatusText('delivered') }}
                    </ion-badge>
                    <span class="date-text">{{ formatDate(reservation.status_history.delivered) }}</span>
                  </div>
                  
                  <!-- Returned -->
                  <div v-if="reservation.status_history.returned" class="history-item">
                    <ion-badge :color="getStatusColor('returned')" class="status-badge">
                      <ion-icon :icon="getStatusIcon('returned')" class="status-icon"></ion-icon>
                      {{ getStatusText('returned') }}
                    </ion-badge>
                    <span class="date-text">{{ formatDate(reservation.status_history.returned) }}</span>
                  </div>
                  
                  <!-- Cancelled -->
                  <div v-if="reservation.status_history.cancelled" class="history-item">
                    <ion-badge :color="getStatusColor('cancelled')" class="status-badge">
                      <ion-icon :icon="getStatusIcon('cancelled')" class="status-icon"></ion-icon>
                      {{ getStatusText('cancelled') }}
                    </ion-badge>
                    <span class="date-text">{{ formatDate(reservation.status_history.cancelled) }}</span>
                  </div>
                </div>
              </ion-label>
            </ion-item>
            <ion-item v-if="!reservationStore.reservationHistory.length && !reservationStore.isLoading">
              <ion-label class="ion-text-center">{{ t('admin.reservations.filters.noResults') }}</ion-label>
            </ion-item>
          </template>
          <ion-item v-else-if="reservationStore.isLoading">
            <ion-label class="ion-text-center">{{ t('common.status.loading') }}</ion-label>
          </ion-item>
          <ion-item v-else>
            <ion-label class="ion-text-center text-red-500">
              {{ t('common.errors.loadingFailed') }}
            </ion-label>
          </ion-item>
        </ion-item-group>
      </ion-list>

      <ion-infinite-scroll
        v-if="!reservationStore.isLoading && 
              reservationStore.historyPagination.currentPage < reservationStore.historyPagination.lastPage"
        @ionInfinite="loadMoreHistory"
        threshold="100px"
      >
        <ion-infinite-scroll-content 
          loading-spinner="bubbles"
          :loading-text="t('common.status.loading')">
        </ion-infinite-scroll-content>
      </ion-infinite-scroll>
    </div>

    <ion-infinite-scroll
      v-if="!reservationStore.isLoading && 
            reservationStore.pagination.currentPage < reservationStore.pagination.lastPage && 
            ['active', 'past'].includes(selectedSegment)"
      @ionInfinite="loadMoreReservations"
      position="bottom"
      threshold="100px"
    >
      <ion-infinite-scroll-content 
        loading-spinner="bubbles"
        :loading-text="t('common.status.loading')">
      </ion-infinite-scroll-content>
    </ion-infinite-scroll>

  </ion-content>
</template>

<script setup lang="ts">
import { onMounted, computed, ref, onUnmounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { alertController } from '@ionic/vue';
import { format } from 'date-fns';
import SearchFilter from '@/components/admin/SearchFilter.vue';
import { wsService } from '@/services/websocket';
import QueuePositionDisplay from '@/components/QueuePositionDisplay.vue';
import { useToast } from '@/composables/useToast';
import { useReservationStore } from '@/stores/reservation';
import { useBookStore } from '@/stores/book';
import { useAdminStore } from '@/stores/admin';
import { 
  checkmarkCircleOutline, 
  timeOutline, 
  alertCircleOutline,
  checkmarkDoneCircleOutline,
  closeCircleOutline,
  returnDownBackOutline
} from 'ionicons/icons';

const { t } = useI18n();
const reservationStore = useReservationStore();
const bookStore = useBookStore();
const adminStore = useAdminStore();
const { showToast } = useToast();

const filters = ref({
  search: '',
  book: null,
  user: null
});

const selectedSegment = ref('active'); // Default to active reservations
const selectedStatus = ref(''); // Default to show all statuses

const handleSegmentChange = async (event: any) => {
  selectedSegment.value = event.detail.value;
  selectedStatus.value = ''; // Reset status filter
  filters.value = { search: '', book: null, user: null }; // Reset all filters
  
  if (event.detail.value === 'history') {
    console.log('Fetching history...');
    try {
      await reservationStore.fetchReservationHistory({ 
        page: 1,
        search: filters.value.search,
        book_id: filters.value.book,
        user_id: filters.value.user
      });
      console.log('History fetched:', reservationStore.reservationHistory);
    } catch (error) {
      console.error('Error fetching history:', error);
    }
  } else if (['active', 'past'].includes(event.detail.value)) {
    await refreshReservations(true);
    await reservationStore.initializeRealTimeUpdates({
      type: event.detail.value === 'active' ? 'active' : 'past'
    });
  }
};

const handleStatusChange = async () => {
  await refreshReservations(true);
  await reservationStore.initializeRealTimeUpdates({
    type: selectedSegment.value === 'active' ? 'active' : 'past',
    status: selectedStatus.value
  });
};

const getStatusColor = (status: string) => {
  const colors = {
    pending: 'warning',
    ready: 'success',
    accepted: 'tertiary',
    delivered: 'primary',
    cancelled: 'medium',
    returned: 'secondary'
  };
  return colors[status] || 'medium';
};

const getStatusText = (status: string) => {
  return t(`reservations.status.${status}`);
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
  
  if (selectedSegment.value === 'history') {
    await reservationStore.fetchReservationHistory({
      page: 1,
      search: filters.value.search,
      book_id: filters.value.book,
      user_id: filters.value.user
    });
  } else {
    await refreshReservations(true);
  }
};

const loadMoreReservations = async (event: any) => {
  if (reservationStore.pagination.currentPage >= reservationStore.pagination.lastPage) {
    event.target.complete();
    return;
  }

  try {
    const nextPage = reservationStore.pagination.currentPage + 1;
    await reservationStore.fetchAdminReservations({
      page: nextPage,
      search: filters.value.search,
      book_id: filters.value.book,
      user_id: filters.value.user,
      status: selectedStatus.value,
      type: selectedSegment.value === 'active' ? 'active' : 'past'
    });
  } catch (error) {
    console.error('Error loading more reservations:', error);
  } finally {
    event.target.complete();
  }
};

const adminReservationAction = async (action: 'cancel' | 'accept' | 'deliver' | 'return', reservation) => {
  try {
    await reservationStore.adminReservationAction(action, reservation.id);
    await showToast(`reservation.messages.${action}Success`, { color: 'success' });
    await refreshReservations(false);
  } catch (error) {
    await showToast('reservation.errors.generic', { 
      color: 'danger',
      duration: 3000
    });
  }
};

const showConfirmation = async (action: string, reservation) => {
  const alert = await alertController.create({
    header: t(`reservation.confirmations.${action}Header`),
    message: t(`reservation.confirmations.${action}Confirm`),
    buttons: [
      {
        text: t('common.actions.no'),
        role: 'cancel'
      },
      {
        text: t('common.actions.yes'),
        role: 'confirm',
        handler: () => {
          adminReservationAction(action, reservation);
        }
      }
    ]
  });
  await alert.present();
};

// Update the activeReservations computed property
const activeReservations = computed(() => {
  if (!Array.isArray(reservationStore.adminRreservations)) {
    console.warn('adminRreservations is not an array:', reservationStore.adminRreservations);
    return [];
  }

  return reservationStore.adminRreservations.filter(reservation => {
    if (!reservation || typeof reservation.status === 'undefined') {
      console.warn('Invalid reservation object:', reservation);
      return false;
    }

    const isActiveStatus = ['pending', 'ready', 'accepted', 'delivered'].includes(reservation.status);
    const matchesSelectedStatus = !selectedStatus.value || reservation.status === selectedStatus.value;
    const matchesSearch = !filters.value.search || 
      (reservation.book?.title?.toLowerCase().includes(filters.value.search.toLowerCase()) ||
       reservation.user?.name?.toLowerCase().includes(filters.value.search.toLowerCase()));
    
    return isActiveStatus && matchesSelectedStatus && matchesSearch;
  });
});

const pastReservations = computed(() => {
  if (!Array.isArray(reservationStore.adminRreservations)) {
    console.warn('adminRreservations is not an array:', reservationStore.adminRreservations);
    return [];
  }

  return reservationStore.adminRreservations.filter(reservation => {
    if (!reservation || typeof reservation.status === 'undefined') {
      console.warn('Invalid reservation object:', reservation);
      return false;
    }

    const isPastStatus = ['returned', 'cancelled'].includes(reservation.status);
    const matchesSelectedStatus = !selectedStatus.value || reservation.status === selectedStatus.value;
    const matchesSearch = !filters.value.search || 
      (reservation.book?.title?.toLowerCase().includes(filters.value.search.toLowerCase()) ||
       reservation.user?.name?.toLowerCase().includes(filters.value.search.toLowerCase()));
    
    return isPastStatus && matchesSelectedStatus && matchesSearch;
  });
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

// Update the refreshReservations function to include all necessary parameters
const refreshReservations = async (resetPage = false) => {
  try {
    const page = resetPage ? 1 : reservationStore.pagination.currentPage;
    await reservationStore.fetchAdminReservations({ 
      page,
      search: filters.value.search,
      book_id: filters.value.book,
      user_id: filters.value.user,
      status: selectedStatus.value,
      type: selectedSegment.value === 'active' ? 'active' : 'past'
    });
  } catch (error) {
    console.error('Error refreshing reservations:', error);
    await showToast('toast.reservation.error', { color: 'danger' });
  }
};

// Update the mounted hook to initialize with correct parameters
onMounted(async () => {
  if (selectedSegment.value === 'history') {
    await reservationStore.fetchReservationHistory({ 
      page: 1,
      search: filters.value.search,
      book_id: filters.value.book,
      user_id: filters.value.user
    });
  } else {
    await refreshReservations(true);
    await reservationStore.initializeRealTimeUpdates({
      type: selectedSegment.value === 'active' ? 'active' : 'past',
      status: selectedStatus.value
    });
  }
  await bookStore.fetchAdminBooks();
  await adminStore.fetchUsers();
});

onUnmounted(() => {
  reservationStore.stopAutoRefresh();
  wsService.close();
});

// Update the watch effect to be more specific and avoid unnecessary refreshes
watch([
  () => selectedSegment.value,
  () => selectedStatus.value,
  () => filters.value.search,
  () => filters.value.book,
  () => filters.value.user
], async (newValues, oldValues) => {
  if (JSON.stringify(newValues) !== JSON.stringify(oldValues)) {
    if (selectedSegment.value === 'history') {
      await reservationStore.fetchReservationHistory({
        page: 1,
        search: filters.value.search,
        book_id: filters.value.book,
        user_id: filters.value.user
      });
    } else {
      await refreshReservations(true);
    }
  }
}, { deep: true });

const loadMoreHistory = async (event: any) => {
  if (reservationStore.historyPagination.currentPage >= reservationStore.historyPagination.lastPage) {
    event.target.complete();
    return;
  }

  try {
    const nextPage = reservationStore.historyPagination.currentPage + 1;
    await reservationStore.fetchReservationHistory({
      page: nextPage,
      search: filters.value.search,
      book_id: filters.value.book,
      user_id: filters.value.user
    });
  } catch (error) {
    console.error('Error loading more history:', error);
  } finally {
    event.target.complete();
  }
};

const getStatusIcon = (status: string) => {
  const icons = {
    pending: timeOutline,
    ready: checkmarkCircleOutline,
    accepted: alertCircleOutline,
    delivered: checkmarkDoneCircleOutline,
    cancelled: closeCircleOutline,
    returned: returnDownBackOutline
  };
  return icons[status] || alertCircleOutline;
};
</script>

<style scoped>
/* General styles */
.ion-padding {
  --padding: 1rem;
}

/* Card styles */
ion-card {
  margin: 1rem 0;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* List styles */
ion-item {
  --padding-start: 1rem;
  --padding-end: 1rem;
  --padding-top: 0.75rem;
  --padding-bottom: 0.75rem;
}

ion-item-divider {
  --padding-start: 1rem;
  --background: var(--ion-color-light);
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* Badge styles */
.status-badge {
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-weight: 500;
  margin-right: 0.5rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  --background: rgba(var(--ion-color-base-rgb), 0.15);
  --color: var(--ion-color-base);
}

.status-icon {
  font-size: 1rem;
  margin-right: 0.25rem;
}

/* History timeline styles */
.reservation-history {
  margin-top: 1rem;
  padding-left: 2rem;
  position: relative;
}

.reservation-history::before {
  content: '';
  position: absolute;
  left: 0.5rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--ion-color-medium-tint);
  opacity: 0.3;
}

.history-item {
  margin: 0.75rem 0;
  position: relative;
  padding: 0.25rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.history-item::before {
  content: '';
  position: absolute;
  left: -1.5rem;
  top: 50%;
  width: 0.5rem;
  height: 0.5rem;
  background: var(--ion-color-medium);
  border-radius: 50%;
  transform: translateY(-50%);
  border: 2px solid var(--ion-background-color);
  z-index: 1;
}

.date-text {
  color: var(--ion-color-medium);
  font-size: 0.9rem;
}

/* Reservation details styles */
.reservation-details {
  margin-top: 0.5rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

.reservation-details span {
  color: var(--ion-color-medium);
  font-size: 0.9rem;
}

/* Title and text styles */
h2.text-lg {
  margin: 0 0 0.5rem 0;
  color: var(--ion-color-dark);
  font-size: 1.1rem;
}

p {
  margin: 0.25rem 0;
  color: var(--ion-color-medium-shade);
}

/* Button styles */
ion-button {
  --padding-start: 1rem;
  --padding-end: 1rem;
  font-weight: 500;
}

/* Search filter styles */
ion-select {
  --padding-start: 0.75rem;
  --padding-end: 0.75rem;
  --padding-top: 0.5rem;
  --padding-bottom: 0.5rem;
  border-radius: 6px;
  background: var(--ion-color-light);
}

/* Infinite scroll styles */
ion-infinite-scroll-content {
  min-height: 4rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Responsive adjustments */
@media (min-width: 768px) {
  ion-card {
    max-width: 800px;
    margin: 1rem auto;
  }

  .reservation-details {
    gap: 1rem;
  }

  h2.text-lg {
    font-size: 1.2rem;
  }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  ion-card {
    --background: var(--ion-color-step-100);
  }

  .history-item::before {
    border-color: var(--ion-background-color-step-150);
  }

  .status-badge {
    --background: rgba(var(--ion-color-base-rgb), 0.2);
  }
}
</style>
