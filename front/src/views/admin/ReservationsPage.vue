<template>
  <admin-layout>
    <ion-content class="ion-padding">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Reservations Management</h1>
      </div>

      <!-- Loading State -->
      <div v-if="adminStore.isLoading" class="flex justify-center p-4">
        <ion-spinner></ion-spinner>
      </div>

      <!-- Reservations List -->
      <ion-list v-else>
        <ion-item-group>
          <ion-item-divider>
            <ion-label>Active Reservations</ion-label>
          </ion-item-divider>

          <ion-item v-for="reservation in activeReservations" :key="reservation.id">
            <ion-label>
              <h2 class="text-lg font-semibold">{{ reservation.book.title }}</h2>
              <p>Reserved by: {{ reservation.user.name }}</p>
              <p>
                <ion-badge :color="getStatusColor(reservation.status)">
                  {{ getStatusText(reservation.status) }}
                </ion-badge>
                <span v-if="reservation.status === 'ready'" class="ml-2">
                  Expires: {{ formatExpiry(reservation.expires_at) }}
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
        </ion-item-group>

        <ion-item-group>
          <ion-item-divider>
            <ion-label>Past Reservations</ion-label>
          </ion-item-divider>

          <ion-item v-for="reservation in pastReservations" :key="reservation.id">
            <ion-label>
              <h2 class="text-lg font-semibold">{{ reservation.book.title }}</h2>
              <p>Reserved by: {{ reservation.user.name }}</p>
              <p>
                <ion-badge :color="getStatusColor(reservation.status)">
                  {{ getStatusText(reservation.status) }}
                </ion-badge>
              </p>
            </ion-label>
          </ion-item>
        </ion-item-group>
      </ion-list>
    </ion-content>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { useAdminStore } from '@/stores/admin';
import { alertController } from '@ionic/vue';
import {
  IonContent, IonList, IonItem, IonLabel, IonBadge,
  IonSpinner, IonButton, IonItemGroup, IonItemDivider
} from '@ionic/vue';

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

const formatExpiry = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};

// Fetch reservations on mount
onMounted(() => {
  adminStore.fetchAllReservations();
});
</script> 