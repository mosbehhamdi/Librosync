<template>
  <admin-layout>
    <ion-content class="ion-padding">
      <!-- Header with Title -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-center">Reservations Management</h1>
      </div>

      <!-- Tabs for Active, Past, and History Reservations -->
      <ion-tabs>
        <ion-tab-bar slot="top">
          <ion-tab-button tab="active">
            <ion-label>Active Reservations</ion-label>
          </ion-tab-button>
          <ion-tab-button tab="past">
            <ion-label>Past Reservations</ion-label>
          </ion-tab-button>
          <ion-tab-button tab="history">
            <ion-label>Reservation History</ion-label>
          </ion-tab-button>
        </ion-tab-bar>

        <!-- Active Reservations Tab -->
        <ion-tab tab="active">
          <!-- Loading State -->
          <div v-if="adminStore.isLoading" class="flex justify-center items-center h-full">
            <ion-spinner></ion-spinner>
          </div>

          <!-- Active Reservations List -->
          <ion-list v-else>
            <ion-item-group>
              <ion-item-divider>
                <ion-label>Active Reservations</ion-label>
              </ion-item-divider>
              <ion-item v-for="reservation in adminStore.activeReservations" :key="reservation.id">
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
                <ion-button 
                  v-if="reservation.status === 'accepted'"
                  slot="end" 
                  fill="clear" 
                  color="success"
                  @click="confirmDeliver(reservation)"
                >
                  Deliver
                </ion-button>
                <ion-button 
                  v-else
                  slot="end" 
                  fill="clear" 
                  color="success"
                  @click="confirmAccept(reservation)"
                >
                  Accept
                </ion-button>
               
              </ion-item>
            </ion-item-group>
          </ion-list>
        </ion-tab>

        <!-- Past Reservations Tab -->
        <ion-tab tab="past">
          <ion-list v-if="!adminStore.isLoading">
            <ion-item-group>
              <ion-item-divider>
                <ion-label>Past Reservations</ion-label>
              </ion-item-divider>
              <ion-item v-for="reservation in adminStore.pastReservations" :key="reservation.id">
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
        </ion-tab>

        <!-- Reservation History Tab -->
        <ion-tab tab="history">
          <ReservationHistoryPage />
        </ion-tab>
      </ion-tabs>
    </ion-content>
  </admin-layout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useAdminStore } from '@/stores/admin';
import { alertController } from '@ionic/vue';
import {
  IonContent, IonList, IonItem, IonLabel, IonBadge,
  IonSpinner, IonButton, IonItemGroup, IonItemDivider,
  IonTabs, IonTabBar, IonTabButton, IonTab
} from '@ionic/vue';
import { format } from 'date-fns';
import ReservationHistoryPage from './ReservationHistoryPage.vue';

const adminStore = useAdminStore();
const socket = ref<WebSocket | null>(null);

const getStatusColor = (status: string) => {
  const colors = {
    pending: 'warning',
    ready: 'success',
    delivered: 'primary',
    cancelled: 'medium',
    accepted: 'tertiary'
  };
  return colors[status] || 'medium';
};

const getStatusText = (status: string) => {
  const texts = {
    pending: 'Waiting',
    ready: 'Ready for Pickup',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
    accepted: 'Accepted'
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
    await adminStore.cancelReservation(id);
  } catch (error) {
    console.error('Error cancelling reservation:', error);
  }
};
const formatExpiry = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};

const confirmAccept = async (reservation) => {
  const alert = await alertController.create({
    header: 'Accept Reservation',
    message: 'Are you sure you want to accept this reservation?',
    buttons: [
      {
        text: 'No',
        role: 'cancel'
      },
      {
        text: 'Yes',
        role: 'confirm',
        handler: () => acceptReservation(reservation.id)
      }
    ]
  });
  await alert.present();
};

const acceptReservation = async (id: number) => {
  try {
    await adminStore.acceptReservation(id);
  } catch (error) {
    console.error('Error accepting reservation:', error);
  }
};

const confirmDeliver = async (reservation) => {
  const alert = await alertController.create({
    header: 'Deliver Reservation',
    message: 'Are you sure you want to mark this reservation as delivered?',
    buttons: [
      {
        text: 'No',
        role: 'cancel'
      },
      {
        text: 'Yes',
        role: 'confirm',
        handler: () => deliverReservation(reservation.id)
      }
    ]
  });
  await alert.present();
};

const deliverReservation = async (id: number) => {
  try {
    await adminStore.deliverReservation(id);
  } catch (error) {
    console.error('Error delivering reservation:', error);
  }
};

// Fetch reservations on mount
onMounted(() => {
  adminStore.fetchAllReservations();
});



</script> 