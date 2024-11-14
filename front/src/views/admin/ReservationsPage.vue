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
                <ion-badge :color="getStatusColor(reservation.status)">
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
                <ion-badge :color="getStatusBadgeColor(reservation.status)">
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
      <ion-list>
        <ion-item-group>
          <ion-item-divider>
            <ion-label>{{ t('admin.reservations.historyTitle') }}</ion-label>
          </ion-item-divider>
          <ion-item v-for="reservation in reservationStore.history" :key="reservation.id">
            <ion-label>
              <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
              <p>{{ t('reservations.reservedBy') }}: {{ reservation.user.name }}</p>
              <p>
                <ion-badge :color="getStatusColor(reservation.status)">
                  {{ getStatusText(reservation.status) }}
                </ion-badge>
                <span class="ml-2">{{ t('reservations.reservedOn') }}: {{ formatDate(reservation.created_at) }}</span>
              </p>
            </ion-label>
          </ion-item>
        </ion-item-group>
      </ion-list>
    </div>

    <ion-infinite-scroll
      v-if="!reservationStore.isLoading && reservationStore.pagination.currentPage < reservationStore.pagination.lastPage && (selectedSegment === 'active' || selectedSegment === 'past')"
      @ionInfinite="loadMoreReservations">
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
import { alertController, toastController } from '@ionic/vue';
import { format } from 'date-fns';
import SearchFilter from '@/components/admin/SearchFilter.vue';
import { wsService } from '@/services/websocket';
import QueuePositionDisplay from '@/components/QueuePositionDisplay.vue';
import { useToast } from '@/composables/useToast';
import { useReservationStore } from '@/stores/reservation';
import { useBookStore } from '@/stores/book';
import { useAdminStore } from '@/stores/admin';

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
  const alert = await alertController.create({
    header: t('reservations.actions.cancelHeader'),
    message: t('reservations.actions.cancelConfirm'),
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

const adminReservationAction = async (action: 'cancel' | 'accept' | 'deliver' | 'return', reservation) => {
  try {
    await reservationStore.adminReservationAction(action, reservation.id);
    await showToast(`toast.reservation.${action}Success`, { color: 'success' });
    await refreshReservations(false);
  } catch (error) {
    await showToast('toast.reservation.error', { 
      color: 'danger',
      duration: 3000
    });
  }
};

// Add helper function for success messages
const getActionSuccessMessage = (action: string) => {
  return t(`admin.reservations.success.${action}`);
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
