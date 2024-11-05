<template>
  <div>
    <ion-button
      :disabled="isLoading"
      :color="reservationButton.color"
      @click="handleReserveClick"
    >
      <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
      <template v-else>
        <ion-icon :icon="reservationButton.icon" slot="start"></ion-icon>
        {{ reservationButton.text }} 
      </template>
    </ion-button>
    <div v-if="queuePosition !== null">
      <p>Your current queue position: {{ queuePosition }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { useAuthStore } from '@/stores/auth';
import { useBookStore } from '@/stores/book';
import { toastController } from '@ionic/vue';
import { bookmarkOutline, timeOutline, checkmarkCircleOutline } from 'ionicons/icons';

const props = defineProps<{
  bookId: number;
  availableCopies: number;
  existingReservation?: any;
  waitingTime?: number;
}>();

const reservationStore = useReservationStore();
const authStore = useAuthStore();
const bookStore = useBookStore();
const isLoading = ref(false);
const reservation = ref(props.existingReservation);
const queuePosition = ref<number | null>(null);

watch(() => props.existingReservation, (newReservation) => {
  reservation.value = newReservation;
});

const hasActiveReservation = computed(() => {
  return reservationStore.reservations.some(res => 
    res.book_id === props.bookId &&
    ['pending', 'ready'].includes(res.status) &&
    res.user_id === authStore.user?.id
  );
});

onMounted(async () => {
  await reservationStore.fetchUserReservations();
  if (reservation.value?.status === 'pending') {
    queuePosition.value = await reservationStore.getQueuePosition(props.bookId);
  }
});

const reservationButton = computed(() => {
  if (hasActiveReservation.value) {
    return {
      color: 'medium',
      icon: checkmarkCircleOutline,
      text: 'Cancel Reservation'
    };
  } else if (props.availableCopies > 0) {
    return {
      color: 'primary',
      icon: bookmarkOutline,
      text: 'Reserve Now'
    };
  } else {
    return {
      color: 'warning',
      icon: timeOutline,
      text: 'Join Waitlist'
    };
  }
});

const showToast = async (message: string, color: string) => {
  const toast = await toastController.create({
    message,
    duration: 3000,
    color
  });
  await toast.present();
};

const handleReserveClick = () => {
  if (hasActiveReservation.value) {
    handleCancelReservation();
  } else if (props.availableCopies > 0) {
    handleReserve();
  } else {
    handleJoinWaitlist();
  }
};

const handleReserve = async () => {
  isLoading.value = true;
  try {
    await reservationStore.reserveBook(props.bookId);
    const message = props.availableCopies > 0 
      ? 'Book reserved successfully! Please pick it up within 48 hours.'
      : 'Added to waitlist. We\'ll notify you when the book is available.';
    await showToast(message, 'success');
  } catch (error: any) {
    await showToast(error.response?.data?.message || 'Failed to reserve book. Please try again later.', 'danger');
  } finally {
    isLoading.value = false;
  }
};

const handleJoinWaitlist = async () => {
  isLoading.value = true;
  try {
    const response = await reservationStore.joinWaitlist(props.bookId);
    if (response?.reservation) {
      reservation.value = response.reservation;
      await showToast('Added to waitlist. We\'ll notify you when the book is available.', 'success');
    }
  } catch (error: any) {
    await showToast(error.response?.data?.message || 'Failed to join waitlist', 'danger');
  } finally {
    isLoading.value = false;
  }
};

const handleCancelReservation = async () => {
  isLoading.value = true;
  try {
    const response = await reservationStore.cancelReservation(reservation.value.id);
    if (response?.message === 'Reservation cancelled successfully') {
      await showToast(response.message, 'success');
      reservation.value = null;
      await bookStore.fetchBooks();
    }
  } catch (error: any) {
    await showToast(error.response?.data?.message || 'Failed to cancel reservation', 'danger');
  } finally {
    isLoading.value = false;
  }
};
</script>
