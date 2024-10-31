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

// Reuse your existing helper functions
const getStatusColor = (status: string) => {
  // Reference existing code
  startLine: 20
  endLine: 27
};

const getStatusText = (status: string) => {
  // Reference existing code
  startLine: 29
  endLine: 35
};

const formatDate = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};
</script> 