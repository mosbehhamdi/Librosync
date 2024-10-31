<template>
  <div>
    <ion-button
      :disabled="isLoading"
      :color="getButtonColor"
      @click="handleReserveClick"
    >
      <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
      <template v-else>
        <ion-icon :icon="getButtonIcon" slot="start"></ion-icon>
        {{ buttonText }}
      </template>
    </ion-button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { toastController } from '@ionic/vue';
import { bookmarkOutline, timeOutline, checkmarkCircleOutline } from 'ionicons/icons';

const props = defineProps<{
  bookId: number;
  availableCopies: number;
  existingReservation?: any;
  waitingTime?: number;
}>();

const reservationStore = useReservationStore();
const isLoading = ref(false);

const handleReserve = async () => {
  try {
    isLoading.value = true;
    await reservationStore.reserveBook(props.bookId);
    
    const toast = await toastController.create({
      message: props.availableCopies > 0 
        ? 'Book reserved successfully! Please pick it up within 48 hours.'
        : 'Added to waitlist. We\'ll notify you when the book is available.',
      duration: 3000,
      color: 'success'
    });
    await toast.present();
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to reserve book',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isLoading.value = false;
  }
};

const getButtonColor = computed(() => {
  if (props.existingReservation) {
    return 'medium';
  } else if (props.availableCopies > 0) {
    return 'primary';
  } else {
    return 'warning';
  }
});

const getButtonIcon = computed(() => {
  if (props.existingReservation) {
    return checkmarkCircleOutline;
  } else if (props.availableCopies > 0) {
    return bookmarkOutline;
  } else {
    return timeOutline;
  }
});

const buttonText = computed(() => {
  if (props.existingReservation) {
    return 'Cancel Reservation';
  } else if (props.availableCopies > 0) {
    return 'Reserve Now';
  } else {
    return 'Join Waitlist';
  }
});

const handleReserveClick = () => {
  if (props.existingReservation) {
    handleCancelReservation();
  } else if (props.availableCopies > 0) {
    handleReserve();
  }
};

const handleCancelReservation = async () => {
  try {
    isLoading.value = true;
    await reservationStore.cancelReservation(props.existingReservation.id);
    
    const toast = await toastController.create({
      message: 'Reservation cancelled successfully',
      duration: 3000,
      color: 'success'
    });
    await toast.present();
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to cancel reservation',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isLoading.value = false;
  }
};
</script> 