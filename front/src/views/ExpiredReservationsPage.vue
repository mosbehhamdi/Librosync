<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Expired Reservations</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-list>
        <ion-item v-for="reservation in expiredReservations" :key="reservation.id">
          <ion-label>
            <h2>{{ reservation.book.title }}</h2>
            <p>{{ reservation.book.authors.join(', ') }}</p>
            <p>
              <ion-badge color="danger">Expired</ion-badge>
              <span class="ml-2">
                Expired on: {{ formatDate(reservation.expires_at) }}
              </span>
            </p>
          </ion-label>
        </ion-item>
      </ion-list>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { format } from 'date-fns';

const reservationStore = useReservationStore();
const expiredReservations = ref([]);

const formatDate = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};

onMounted(async () => {
  await reservationStore.fetchExpiredReservations();
  expiredReservations.value = reservationStore.expiredReservations;
});
</script>
