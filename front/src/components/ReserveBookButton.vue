<template>
  <div>
    <ion-button
      :disabled="isLoading"
      :color="getButtonColor"
      @click="handleReserveClick"
    >
      <ion-icon :icon="getButtonIcon" slot="start"></ion-icon>
      {{ buttonText }}
    </ion-button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { IonButton, IonIcon, toastController } from '@ionic/vue';
import { bookmarkOutline, timeOutline, checkmarkCircleOutline } from 'ionicons/icons';

const props = defineProps<{
  bookId: number;
  availableCopies: number;
  existingReservation?: any;
}>();

const reservationStore = useReservationStore();
const isLoading = ref(false);

const getButtonColor = computed(() => {
  if (props.existingReservation) {
    return props.existingReservation.status === 'ready' ? 'success' : 'warning';
  }
  return props.availableCopies > 0 ? 'primary' : 'medium';
});

const getButtonIcon = computed(() => {
  if (props.existingReservation) {
    return props.existingReservation.status === 'ready' 
      ? checkmarkCircleOutline 
      : timeOutline;
  }
  return bookmarkOutline;
});

const buttonText = computed(() => {
  if (props.existingReservation) {
    return props.existingReservation.status === 'ready'
      ? 'Ready for Pickup'
      : `Queue Position: ${props.existingReservation.queue_position}`;
  }
  return props.availableCopies > 0 ? 'Reserve Now' : 'Join Waitlist';
});

const handleReserveClick = async () => {
  if (props.existingReservation) {
    // Show reservation details or cancel option
    return;
  }

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
      message: error.message || 'Failed to reserve book',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isLoading.value = false;
  }
};
</script> 