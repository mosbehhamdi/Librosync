<template>
  <div>
    <ion-button
      :disabled="isLoading"
      :color="reservationButton.color"
      @click="handleReservationAction(reservationButton.action)"
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

const { bookId, availableCopies, existingReservation } = defineProps<{
  bookId: number;
  availableCopies: number;
  existingReservation?: any;
}>();

// Define emits
const emit = defineEmits(['reservationUpdated']);

const reservationStore = useReservationStore();
const bookStore = useBookStore();
const isLoading = ref(false);
const reservation = ref(existingReservation);
const queuePosition = ref<number | null>(null);

watch(() => existingReservation, (newReservation) => {
  reservation.value = newReservation;
});

onMounted(async () => {
  if (reservation.value?.status === 'pending') {
    queuePosition.value = await reservationStore.getQueuePosition(bookId);
  }
});

const reservationButton = computed(() => {
  const buttonConfig = {
    color: 'warning',
    icon: timeOutline,
    text: 'Join Waitlist',
    action: 'joinWaitlist'
  };

  if (reservation.value) {
    buttonConfig.color = 'medium';
    buttonConfig.icon = checkmarkCircleOutline;
    buttonConfig.text = 'Cancel Reservation';
    buttonConfig.action = 'cancel';
  } else if (availableCopies > 0) {
    buttonConfig.color = 'primary';
    buttonConfig.icon = bookmarkOutline;
    buttonConfig.text = 'Reserve Now';
    buttonConfig.action = 'reserve';
  }

  return buttonConfig;
});

const showToast = async (message: string, color: string) => {
  const toast = await toastController.create({
    message,
    duration: 3000,
    color,
    position: 'top',
    cssClass: 'custom-toast',
    buttons: [
      {
        text: 'Close',
        role: 'cancel'
      }
    ]
  });
  await toast.present();
};

const updateReservation = async () => {
  emit('reservationUpdated', bookId);
};

const handleReservationAction = async (actionType: string) => {
  isLoading.value = true;
  try {
    const response = await reservationStore.userReservationAction(
      actionType, 
      actionType === 'cancel' ? reservation.value?.id : undefined,
      actionType !== 'cancel' ? bookId : undefined
    );

    if (response) {
      reservation.value = actionType === 'cancel' ? null : response.reservation;
      await showToast(
        actionType === 'cancel' ? 'Reservation cancelled successfully' : 'Reservation sent successfully',
        'success'
      );
      await updateReservation();
    }
  } catch (error: any) {
    await handleReservationError(error);
  } finally {
    isLoading.value = false;
  }
};

const handleReservationError = async (error: any) => {
  let errorMessage = 'Failed to process reservation';
  let color = 'danger';

  // Handle specific error messages from the backend
  if (error.response?.data?.message) {
    errorMessage = error.response.data.message;
    
    // Use warning color for limit-related messages
    if (errorMessage.includes('Maximum number of') || 
        errorMessage.includes('overdue books')) {
      color = 'warning';
    }
  }

  await showToast(errorMessage, color);
};

// Optional: Debugging watch to log reservation changes
watch(reservation, () => {
  console.log("Reservation state has changed:", reservation.value);
});
</script>
