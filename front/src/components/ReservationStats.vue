<template>
  <ion-card v-if="statistics">
    <ion-card-header>
      <ion-card-title>Your Reservation Statistics</ion-card-title>
    </ion-card-header>
    <ion-card-content>
      <ion-list>
        <ion-item>
          <ion-label>Total Reservations</ion-label>
          <ion-note slot="end">{{ statistics.total }}</ion-note>
        </ion-item>
        <ion-item>
          <ion-label>Currently Active</ion-label>
          <ion-note slot="end">{{ statistics.active }}</ion-note>
        </ion-item>
        <ion-item>
          <ion-label>Average Wait Time</ion-label>
          <ion-note slot="end">{{ statistics.avgWaitTime }} days</ion-note>
        </ion-item>
      </ion-list>
    </ion-card-content>
  </ion-card>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { useReservationStore } from '@/stores/reservation';

const reservationStore = useReservationStore();

onMounted(() => {
  reservationStore.getReservationStatistics();
});

const statistics = computed(() => reservationStore.statistics);
</script> 