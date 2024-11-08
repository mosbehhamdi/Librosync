<template>
    <ion-content class="ion-padding">
      <ion-list>
        <ion-item v-for="reservation in reservationStore.reservationHistory" :key="reservation.id">
          <ion-label>
            <h2 class="text-lg font-semibold">{{ reservation.book.title }}</h2>
            <p>Reserved by: {{ reservation.user.name }}</p>
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
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { format } from 'date-fns';

const reservationStore = useReservationStore();

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

onMounted(() => {
  reservationStore.fetchReservationHistory();
});
</script> 