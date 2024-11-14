<template>
  <ion-page>
    <ion-content class="ion-padding">
      <!-- Horizontal Menu for Reservation Types -->
      <ion-segment v-model="selectedSegment" @ionChange="handleSegmentChange">
        <ion-segment-button value="active">{{ t('reservations.labels.activeTitle') }}</ion-segment-button>
        <ion-segment-button value="past">{{ t('reservations.labels.pastTitle') }}</ion-segment-button>
        <ion-segment-button value="history">{{ t('reservations.labels.historyTitle') }}</ion-segment-button>
      </ion-segment>

      <!-- Status Filter -->
      <ion-card class="mb-4" v-if="['active', 'past'].includes(selectedSegment)">
        <ion-card-content>
          <ion-item>
            <ion-select v-model="selectedStatus" :placeholder="t('reservations.filters.statusPlaceholder')" @ionChange="handleStatusChange">
              <template v-if="selectedSegment === 'active'">
                <ion-select-option value="pending">{{ t('reservations.status.pending') }}</ion-select-option>
                <ion-select-option value="ready">{{ t('reservations.status.ready') }}</ion-select-option>
                <ion-select-option value="accepted">{{ t('reservations.status.accepted') }}</ion-select-option>
                <ion-select-option value="delivered">{{ t('reservations.status.delivered') }}</ion-select-option>
              </template>
              <template v-else>
                <ion-select-option value="delivered">{{ t('reservations.status.delivered') }}</ion-select-option>
                <ion-select-option value="cancelled">{{ t('reservations.status.cancelled') }}</ion-select-option>
              </template>
            </ion-select>
          </ion-item>
        </ion-card-content>
      </ion-card>

      <!-- Active Reservations -->
      <div v-if="selectedSegment === 'active'">
        <ion-list>
          <ion-item v-for="reservation in filteredActiveReservations" :key="reservation.id">
            <ion-label>
              <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
              <p>{{ reservation.book?.authors.join(', ') }}</p>
              <div class="reservation-status">
                <ion-badge :color="getStatusColor(reservation.status)">
                  {{ t(`reservations.status.${reservation.status}`) }}
                </ion-badge>
                <QueuePositionDisplay
                  :position="reservation.queue_position"
                  :status="reservation.status"
                  :estimatedWaitTime="reservation.book?.waiting_time"
                />
                <span v-if="reservation.expires_at" class="ml-2">
                  {{ t('reservations.labels.expiresOn') }}: {{ formatDate(reservation.expires_at) }}
                </span>
              </div>
            </ion-label>
            <ion-button 
              slot="end" 
              fill="clear" 
              color="danger"
              @click="confirmCancel(reservation)"
            >
              {{ t('common.actions.cancel') }}
            </ion-button>
          </ion-item>
          <ion-item v-if="filteredActiveReservations.length === 0">
            <ion-label class="ion-text-center">{{ t('reservations.labels.noActive') }}</ion-label>
          </ion-item>
        </ion-list>
      </div>

      <!-- Past Reservations -->
      <div v-else-if="selectedSegment === 'past'">
        <ion-list>
          <ion-item v-for="reservation in filteredPastReservations" :key="reservation.id">
            <ion-label>
              <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
              <p>{{ reservation.book?.authors.join(', ') }}</p>
              <p>
                <ion-badge :color="getStatusColor(reservation.status)">
                  {{ t(`reservations.status.${reservation.status}`) }}
                </ion-badge>
              </p>
            </ion-label>
          </ion-item>
          <ion-item v-if="filteredPastReservations.length === 0">
            <ion-label class="ion-text-center">{{ t('reservations.labels.noPast') }}</ion-label>
          </ion-item>
        </ion-list>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { alertController } from '@ionic/vue';
import { format } from 'date-fns';
import QueuePositionDisplay from '@/components/QueuePositionDisplay.vue';
import { useToast } from '@/composables/useToast';
import { useI18n } from 'vue-i18n';

const reservationStore = useReservationStore();
const selectedSegment = ref('active');
const selectedStatus = ref('');
const { showToast } = useToast();
const { t } = useI18n();

onMounted(async () => {
  await reservationStore.fetchUserReservations();
});

onUnmounted(() => {
  reservationStore.stopAutoRefresh();
});

const handleSegmentChange = (event: any) => {
  selectedSegment.value = event.detail.value;
  selectedStatus.value = ''; // Reset status filter when changing segments
};

const handleStatusChange = () => {
  // Status filter is handled by computed properties
};

// Filtered computed properties
const filteredActiveReservations = computed(() => {
  if (!Array.isArray(reservationStore.reservations)) {
    console.warn('reservations is not an array:', reservationStore.reservations);
    return [];
  }
  
  const active = reservationStore.reservations.filter(r => 
    reservationStore.activeStatuses.includes(r.status)
  );
  
  return selectedStatus.value
    ? active.filter(r => r.status === selectedStatus.value)
    : active;
});

const filteredPastReservations = computed(() => {
  if (!Array.isArray(reservationStore.reservations)) {
    return [];
  }
  
  const past = reservationStore.reservations.filter(r => 
    ['returned', 'cancelled'].includes(r.status)
  );
  
  return selectedStatus.value
    ? past.filter(r => r.status === selectedStatus.value)
    : past;
});

// Helper functions from your existing code
const getStatusColor = (status: string) => {
  const colors = {
    pending: 'warning',
    ready: 'success',
    accepted: 'tertiary',
    delivered: 'primary',
    cancelled: 'medium'
  };
  return colors[status] || 'medium';
};

const getStatusText = (status: string) => {
  return t(`reservations.status.${status}`);
};

const formatDate = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};

const confirmCancel = async (reservation) => {
  const alert = await alertController.create({
    header: t('reservations.cancel.title'),
    message: t('reservations.cancel.message'),
    buttons: [
      {
        text: t('common.actions.cancel'),
        role: 'cancel'
      },
      {
        text: t('common.actions.confirm'),
        role: 'destructive',
        handler: () => cancelReservation(reservation.id)
      }
    ]
  });
  await alert.present();
};

const cancelReservation = async (id: number) => {
  try {
    await reservationStore.userReservationAction('cancel', id);
    await reservationStore.fetchUserReservations();
    await showToast('toast.reservation.cancelSuccess', { 
      color: 'success',
      translate: true 
    });
  } catch (error) {
    console.error('Error cancelling reservation:', error);
    await showToast('toast.reservation.error', { 
      color: 'danger',
      translate: true 
    });
  }
};
</script> 