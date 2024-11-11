<template>
  <ion-content class="ion-padding">
    <ion-toolbar>
      <ion-buttons slot="end">
        <ion-button router-link="/user/reservations/history">
          View Reservation History
        </ion-button>
        <ion-button router-link="/reservations/expired">
          Expired Reservations
          <ion-icon :icon="timeOutline" slot="end"></ion-icon>
        </ion-button>
      </ion-buttons>
    </ion-toolbar>

    <ion-list>
      <!-- Active Reservations -->
      <ion-item-group>
        <ion-item-divider>
          <ion-label>Active Reservations</ion-label>
        </ion-item-divider>

        <ion-item v-for="reservation in activeReservations" :key="reservation.id">
          <ion-label>
            <h2>{{ reservation.book.title }}</h2>
            <p>{{ reservation.book.authors.join(', ') }}</p>
            <p>
              <ion-badge :color="getStatusColor(reservation.status)">
                {{ getStatusText(reservation.status) }}
              </ion-badge>
              <span v-if="reservation.status === 'ready'" class="ml-2">
                Expires: {{ formatDate(reservation.expires_at) }}
              </span>
              <span v-if="reservation.status === 'pending'" class="ml-2">
                Queue Position: {{ reservation.queue_position }}
              </span>
            </p>
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

        <ion-item v-if="activeReservations.length === 0">
          <ion-label class="ion-text-center">
            No active reservations
          </ion-label>
        </ion-item>
      </ion-item-group>

      <!-- Past Reservations -->
      <ion-item-group>
        <ion-item-divider>
          <ion-label>Past Reservations</ion-label>
        </ion-item-divider>

        <ion-item v-for="reservation in pastReservations" :key="reservation.id">
          <ion-label>
            <h2>{{ reservation.book.title }}</h2>
            <p>{{ reservation.book.authors.join(', ') }}</p>
            <p>
              <ion-badge :color="getStatusColor(reservation.status)">
                {{ getStatusText(reservation.status) }}
              </ion-badge>
            </p>
          </ion-label>
        </ion-item>

        <ion-item v-if="pastReservations.length === 0">
          <ion-label class="ion-text-center">
            No past reservations
          </ion-label>
        </ion-item>
      </ion-item-group>
    </ion-list>
  </ion-content>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { alertController } from '@ionic/vue';
import { format } from 'date-fns';

const reservationStore = useReservationStore();

const activeReservations = computed(() => 
  reservationStore.reservations.filter(r => 
    ['pending', 'ready'].includes(r.status)
  )
);

const pastReservations = computed(() => 
  reservationStore.reservations.filter(r => 
    ['delivered', 'cancelled'].includes(r.status)
  )
);

const getStatusColor = (status: string) => {
  const colors = {
    pending: 'warning',
    ready: 'success',
    delivered: 'primary',
    cancelled: 'medium'
  };
  return colors[status] || 'medium';
};

const getStatusText = (status: string) => {
  const texts = {
    pending: 'Waiting',
    ready: 'Ready for Pickup',
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
    await reservationStore.userReservationAction('cancel', id);
  } catch (error) {
    console.error('Error cancelling reservation:', error);
  }
};

onMounted(async () => {
  await reservationStore.fetchUserReservations();
});
</script> 