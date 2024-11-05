<template>
  <ion-card v-if="statistics">
    <ion-card-header>
      <ion-card-title>Reservation Statistics</ion-card-title>
    </ion-card-header>
    <ion-card-content>
      <ion-list>
        <ion-item>
          <ion-label>Total Reservations</ion-label>
          <ion-note slot="end">{{ statistics.total }}</ion-note>
        </ion-item>
        <ion-item>
          <ion-label>Active Reservations</ion-label>
          <ion-note slot="end">{{ statistics.active }}</ion-note>
        </ion-item>
        <ion-item>
          <ion-label>Delivered Reservations</ion-label>
          <ion-note slot="end">{{ statistics.delivered }}</ion-note>
        </ion-item>
        <ion-item>
          <ion-label>Cancelled Reservations</ion-label>
          <ion-note slot="end">{{ statistics.cancelled }}</ion-note>
        </ion-item>
      </ion-list>
    </ion-card-content>
  </ion-card>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { useAdminStore } from '@/stores/admin';

const adminStore = useAdminStore();

onMounted(() => {
  adminStore.fetchReservationStatistics();
});

const statistics = computed(() => adminStore.statistics);
</script> 