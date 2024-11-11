<template>
  <admin-layout>
    <ion-content class="ion-padding overflow-y-auto">
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
          <div v-if="reservationStore.isLoading && activeReservations.length === 0"
            class="flex justify-center items-center h-full">
            <ion-spinner></ion-spinner>
          </div>

          <!-- Active Reservations List -->
          <ion-list v-else>
            <ion-item-group>
              <ion-item-divider>
                <ion-label>Active Reservations</ion-label>
              </ion-item-divider>
              <ion-item v-for="reservation in activeReservations" :key="reservation.id">
                <ion-label>
                  <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
                  <p>Reserved by: {{ reservation.user.name }}</p>
                  <p>
                    <ion-badge :color="reservation.statusColor">
                      {{ reservation.statusText }}
                    </ion-badge>
                    <span v-if="reservation.expiry" class="ml-2">
                      Expires: {{ reservation.expiry }}
                    </span>
                    <span v-if="reservation.queuePosition" class="ml-2">
                      Queue Position: {{ reservation.queuePosition }}
                    </span>
                  </p>
                </ion-label>

                <!-- Cancel Button Always Visible -->
                <ion-button slot="end" fill="clear" color="danger" @click="showConfirmation('cancel', reservation)">
                  Cancel
                </ion-button>

                <!-- Action Button (Deliver or Accept) -->
                <ion-button v-if="reservation.status === 'accepted'" slot="end" fill="clear" color="success"
                  @click="showConfirmation('deliver', reservation)">
                  Deliver
                </ion-button>
                <ion-button v-else-if="reservation.status === 'ready'" slot="end" fill="clear" color="success"
                  @click="showConfirmation('accept', reservation)">
                  Accept
                </ion-button>
              </ion-item>
            </ion-item-group>
          </ion-list>

          <!-- Infinite Scroll for Active Reservations -->
          <ion-infinite-scroll
            v-if="!reservationStore.isLoading && reservationStore.pagination.currentPage < reservationStore.pagination.lastPage"
            @ionInfinite="loadMoreReservations">
            <ion-infinite-scroll-content></ion-infinite-scroll-content>
          </ion-infinite-scroll>
        </ion-tab>

        <!-- Past Reservations Tab -->
        <ion-tab tab="past">
          <ion-list v-if="!reservationStore.isLoading">
            <ion-item-group>
              <ion-item-divider>
                <ion-label>Past Reservations</ion-label>
              </ion-item-divider>
              <ion-item v-for="reservation in reservationStore.pastReservations" :key="reservation.id">
                <ion-label>
                  <h2 class="text-lg font-semibold">{{ reservation.book?.title }}</h2>
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

          <!-- Infinite Scroll for Past Reservations -->
          <ion-infinite-scroll
            v-if="!reservationStore.isLoading && reservationStore.pagination.currentPage < reservationStore.pagination.lastPage"
            @ionInfinite="loadMorePastReservations">
            <ion-infinite-scroll-content></ion-infinite-scroll-content>
          </ion-infinite-scroll>
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
import { onMounted, computed } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useReservationStore } from '@/stores/reservation';
import { alertController } from '@ionic/vue';
import {
  IonContent, IonList, IonItem, IonLabel, IonBadge,
  IonSpinner, IonButton, IonItemGroup, IonItemDivider,
  IonTabs, IonTabBar, IonTabButton, IonTab
} from '@ionic/vue';
import { format } from 'date-fns';
import ReservationHistoryPage from './ReservationHistoryPage.vue';
import {
  IonInfiniteScroll, IonInfiniteScrollContent
} from '@ionic/vue';
const reservationStore = useReservationStore();

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

const formatExpiry = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};

// Reusable function to show confirmation alerts
const showConfirmation = async (action: 'cancel' | 'accept' | 'deliver', reservation) => {
  const actionText = action.charAt(0).toUpperCase() + action.slice(1);
  const alert = await alertController.create({
    header: `${actionText} Reservation`,
    message: `Are you sure you want to ${action} this reservation?`,
    buttons: [
      {
        text: 'No',
        role: 'cancel'
      },
      {
        text: 'Yes',
        role: 'confirm',
        handler: () => adminReservationAction(action, reservation)
      }
    ]
  });
  await alert.present();
};

const adminReservationAction = (action: 'cancel' | 'accept' | 'deliver', reservation) => {
  reservationStore.adminReservationAction(action, reservation.id)
    .then(resp => {
      // Update the local reservation state with the updated reservation data
      const index = reservationStore.adminRreservations.findIndex(r => r.id === reservation.id);
      if (index !== -1) {
        reservationStore.adminRreservations[index] = resp.reservation;
      }
    })
    .catch(error => {
      console.error('Error performing reservation action:', error);
    });
};

const activeReservations = computed(() => {
  return reservationStore.activeReservations.map(reservation => ({
    ...reservation,
    statusColor: getStatusColor(reservation.status),
    statusText: getStatusText(reservation.status),
    expiry: reservation.status === 'ready' ? formatExpiry(reservation.expires_at) : null,
    queuePosition: reservation.status === 'pending' ? reservation.queue_position : null,
  }));
});

const loadMoreReservations = async (event: any) => {
  if (reservationStore.pagination.currentPage >= reservationStore.pagination.lastPage) {
    event.target.complete(); // Complete the event if on the last page
    return;
  }

  try {
    await reservationStore.fetchMoreReservations(); // Fetch more reservations
  } catch (error) {
    console.error('Error loading more reservations:', error);
  } finally {
    event.target.complete(); // Complete the infinite scroll event
  }
};

const loadMorePastReservations = async (event: any) => {
  if (reservationStore.pagination.currentPage >= reservationStore.pagination.lastPage) {
    event.target.complete(); // Complete the event if on the last page
    return;
  }

  try {
    await reservationStore.fetchMorePastReservations(); // Fetch more past reservations
  } catch (error) {
    console.error('Error loading more past reservations:', error);
  } finally {
    event.target.complete(); // Complete the infinite scroll event
  }
};

// Fetch reservations on mount
onMounted(() => {
  reservationStore.fetchAdminReservations();
});

</script>