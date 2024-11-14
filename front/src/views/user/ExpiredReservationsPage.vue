<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>{{ t('reservations.labels.expiredTitle') }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-list>
        <ion-item v-for="reservation in expiredReservations" :key="reservation.id">
          <ion-label>
            <h2>{{ reservation.book.title }}</h2>
            <p>{{ reservation.book.authors.join(', ') }}</p>
            <p>
              <ion-badge color="danger">{{ t('reservations.status.expired') }}</ion-badge>
              <span class="ml-2">
                {{ t('reservations.labels.expiredOn') }}: {{ formatDate(reservation.expires_at) }}
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
import { useI18n } from 'vue-i18n';
import { useReservationStore } from '@/stores/reservation';
import { format } from 'date-fns';

const { t } = useI18n();
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
