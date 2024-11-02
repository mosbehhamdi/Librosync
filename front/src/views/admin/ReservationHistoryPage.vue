<template>
  <admin-layout>
    <ion-content class="ion-padding">
      <h1 class="text-2xl font-bold mb-4">Reservation History</h1>
      <ion-list>
        <ion-item v-for="reservation in adminStore.reservationHistory" :key="reservation.id">
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
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useAdminStore } from '@/stores/admin';
import { format } from 'date-fns';

const adminStore = useAdminStore();

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

onMounted(() => {
  adminStore.fetchReservationHistory();
});
</script> 