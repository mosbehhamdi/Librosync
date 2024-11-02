<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Reservation History</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-list>
        <ion-item v-for="reservation in reservationStore.history" :key="reservation.id">
          <ion-label>
            <h2>{{ reservation.book.title }}</h2>
            <p>{{ reservation.book.authors.join(', ') }}</p>
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
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { format } from 'date-fns';

const reservationStore = useReservationStore();

onMounted(() => {
  reservationStore.getReservationHistory();
});

const getStatusColor = (status: string) => {
  const colors = {
    pending: 'warning',
    ready: 'success',
    completed: 'primary',
    cancelled: 'medium'
  };
  return colors[status] || 'medium';
};

const getStatusText = (status: string) => {
  const texts = {
    pending: 'Waiting',
    ready: 'Ready for Pickup',
    completed: 'Completed',
    cancelled: 'Cancelled'
  };
  return texts[status] || status;
};

const formatDate = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};
</script> 