<template>
  <div>
    <ion-button
      :disabled="isLoading"
      :color="reservationButton.color"
      @click="reservationButton.clickEvent"
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
import { computed, ref, onMounted, watch, defineProps, defineEmits } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { useBookStore } from '@/stores/book';
import { toastController } from '@ionic/vue';
import { bookmarkOutline, timeOutline, checkmarkCircleOutline } from 'ionicons/icons';

const props = defineProps<{
  bookId: number;
  availableCopies: number;
  existingReservation?: any;
  waitingTime?: number;
}>();

// Define emits
const emit = defineEmits(['reservationUpdated']);

const reservationStore = useReservationStore();
const bookStore = useBookStore();
const isLoading = ref(false);
const reservation = ref(props.existingReservation);
const queuePosition = ref<number | null>(null);

watch(() => props.existingReservation, (newReservation) => {
  reservation.value = newReservation;
});

onMounted(async () => {
  if (reservation.value?.status === 'pending') {
    queuePosition.value = await reservationStore.getQueuePosition(props.bookId);
  }
});

const reservationButton = computed(() => {
  if (reservation.value) {
    return {
      color: 'medium',
      icon: checkmarkCircleOutline,
      text: 'Cancel Reservation',
      clickEvent: handleCancelReservation
    };
  } else if (props.availableCopies > 0) {
    return {
      color: 'primary',
      icon: bookmarkOutline,
      text: 'Reserve Now',
      clickEvent: handleReserve
    };
  } else {
    return {
      color: 'warning',
      icon: timeOutline,
      text: 'Join Waitlist',
      clickEvent: handleJoinWaitlist
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

const updateReservation = async () => {
 // reservation.value = await reservationStore.fetchUserReservationByBookId(props.bookId);
  emit('reservationUpdated', props.bookId);
};

const handleAction = async (action: () => Promise<any>, successMessage: string) => {
  isLoading.value = true;
  try {
    const response = await action();
    if (response?.reservation) {
      reservation.value = response.reservation;
    }
    await showToast(successMessage, 'success');
    await updateReservation();
  } catch (error: any) {
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};

const handleReserve = () => handleAction(
  () => reservationStore.reserveBook(props.bookId),
  props.availableCopies > 0 
    ? 'Book reserved successfully! Please pick it up within 48 hours.'
    : 'Added to waitlist. We\'ll notify you when the book is available.'
);

const handleJoinWaitlist = () => handleAction(
  () => reservationStore.joinWaitlist(props.bookId),
  'Added to waitlist. We\'ll notify you when the book is available.'
);

const handleCancelReservation = () => handleAction(
  async () => {
    const response = await reservationStore.cancelReservation(reservation.value.id);
    if (response?.message === 'Reservation cancelled successfully') {
      reservation.value = null;
      await bookStore.fetchBooks();
    }
    return response;
  },
  'Reservation cancelled successfully'
);

// Optional: Debugging watch to log reservation changes
watch(reservation, () => {
  console.log("Reservation state has changed:", reservation.value);
});
</script>
