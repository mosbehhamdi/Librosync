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
        {{ t(`reservation.actions.${reservationButton.text}`) }}
      </template>
    </ion-button>
    <div v-if="queuePosition !== null">
      <p>{{ t('reservation.queue.position', { position: queuePosition }) }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch, defineProps, defineEmits } from 'vue';
import { useReservationStore } from '@/stores/reservation';
import { useBookStore } from '@/stores/book';
import { bookmarkOutline, timeOutline, checkmarkCircleOutline } from 'ionicons/icons';
import { useI18n } from 'vue-i18n';
import { useRtl } from '@/composables/useRtl';
import { useToast } from '@/composables/useToast';
import { useValidationErrors } from '@/composables/useValidationErrors';

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

const { t } = useI18n();
const { isRtl } = useRtl();
const { showToast } = useToast();
const { handleValidationErrors } = useValidationErrors();

const reservationButton = computed(() => {
  const buttonConfig = {
    color: 'warning',
    icon: timeOutline,
    text: 'joinWaitlist',
    action: 'joinWaitlist'
  };

  if (reservation.value) {
    buttonConfig.color = 'medium';
    buttonConfig.icon = checkmarkCircleOutline;
    buttonConfig.text = 'cancel';
    buttonConfig.action = 'cancel';
  } else if (availableCopies > 0) {
    buttonConfig.color = 'primary';
    buttonConfig.icon = bookmarkOutline;
    buttonConfig.text = 'reserve';
    buttonConfig.action = 'reserve';
  }

  return buttonConfig;
});

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
      await showToast(`reservation.messages.${actionType}Success`, { 
        color: 'success' 
      });
      await updateReservation();
    }
  } catch (error) {
    await handleValidationErrors(error);
  } finally {
    isLoading.value = false;
  }
};

// Optional: Debugging watch to log reservation changes
watch(reservation, () => {
  console.log("Reservation state has changed:", reservation.value);
});
</script>
