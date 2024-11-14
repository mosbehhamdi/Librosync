<template>
  <ion-page>
    <ion-content class="ion-padding">
      <!-- Horizontal Menu for Reservation Types -->
      <ion-segment v-model="selectedSegment" @ionChange="handleSegmentChange">
        <ion-segment-button value="active">Active Reservations</ion-segment-button>
        <ion-segment-button value="past">Past Reservations</ion-segment-button>
        <ion-segment-button value="history">Reservation History</ion-segment-button>
      </ion-segment>

      <!-- Status Filter for Active/Past Reservations -->
      <ion-card class="mb-4" v-if="['active', 'past'].includes(selectedSegment)">
        <ion-card-content>
          <ion-item>
            <ion-select v-model="selectedStatus" placeholder="Filter by Status" @ionChange="handleStatusChange">
              <template v-if="selectedSegment === 'active'">
                <ion-select-option value="pending">Waiting</ion-select-option>
                <ion-select-option value="ready">Ready for Pickup</ion-select-option>
                <ion-select-option value="accepted">Accepted</ion-select-option>
                <ion-select-option value="delivered">Currently Delivered</ion-select-option>
              </template>
              <template v-else>
                <ion-select-option value="delivered">Delivered</ion-select-option>
                <ion-select-option value="cancelled">Cancelled</ion-select-option>
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
              </div>
            </ion-label>
            <ion-button 
              slot="end" 
              fill="clear" 
              color="danger"
              @click="confirmCancel(reservation)"
            >
              Cancel
            </ion-button>
          </ion-item>
          <ion-item v-if="filteredActiveReservations.length === 0">
            <ion-label class="ion-text-center">No active reservations</ion-label>
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
                  {{ getStatusText(reservation.status) }}
                </ion-badge>
              </p>
            </ion-label>
          </ion-item>
          <ion-item v-if="filteredPastReservations.length === 0">
            <ion-label class="ion-text-center">No past reservations</ion-label>
          </ion-item>
        </ion-list>
      </div>

      <!-- Reservation History -->
      <div v-else-if="selectedSegment === 'history'">
        <ion-list>
          <ion-item v-for="reservation in reservationStore.history" :key="reservation.id">
            <ion-label>
              <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
              <p>{{ reservation.book?.authors.join(', ') }}</p>
              <p>
                <ion-badge :color="getStatusColor(reservation.status)">
                  {{ getStatusText(reservation.status) }}
                </ion-badge>
                <span class="ml-2">
                  Reserved on: {{ formatDate(reservation.created_at) }}
                </span>
              </p>
            </ion-label>
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

const reservationStore = useReservationStore();
const selectedSegment = ref('active');
const selectedStatus = ref('');

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
  const texts = {
    pending: 'Waiting',
    ready: 'Ready for Pickup',
    accepted: 'Accepted',
    delivered: 'Delivered',
    cancelled: 'Cancelled'
  };
  return texts[status] || status;
};

const formatDate = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};

const confirmCancel = async (reservation) => {
  const alert = await alertController.create({
    header: 'Cancel Reservation',
    message: 'Are you sure you want to cancel this reservation?',
    buttons: [
      {
        text: 'No',
        role: 'cancel'
      },
      {
        text: 'Yes',
        role: 'confirm',
        handler: () => cancelReservation(reservation.id)
      }
    ]
  });
  await alert.present();
};

const cancelReservation = async (id: number) => {
  try {
    const response = await reservationStore.userReservationAction('cancel', id);
    await reservationStore.fetchUserReservations(); // Refresh the list
    await presentToast('Reservation cancelled successfully', 'success');
  } catch (error) {
    console.error('Error cancelling reservation:', error);
    await presentToast('Failed to cancel reservation', 'danger');
  }
};
</script> 