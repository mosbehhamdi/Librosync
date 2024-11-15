<template>
  <div :class="['queue-position', rtlClass]" class="queue-position-wrapper" v-if="showPosition">
    <div class="queue-position">
      <ion-chip :color="positionColor" class="position-chip">
        <ion-icon :icon="timeOutline" v-if="isPending"></ion-icon>
        <ion-icon :icon="checkmarkCircleOutline" v-if="!isPending"></ion-icon>
        <ion-label>{{ positionText }}</ion-label>
      </ion-chip>
    </div>
    
    <div v-if="showWaitTime" class="wait-time">
      <ion-note color="medium">
        <ion-icon :icon="timeOutline"></ion-icon>
        {{ t('reservations.waitingTime.estimate') }}: {{ estimatedWaitTime }} {{ t('reservations.waitingTime.days') }}
      </ion-note>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { IonChip, IonLabel, IonIcon, IonNote } from '@ionic/vue';
import { timeOutline, checkmarkCircleOutline } from 'ionicons/icons';
import { useRtl } from '@/composables/useRtl';
import { useI18n } from 'vue-i18n';

interface Props {
  position: number | null;
  status: string;
  estimatedWaitTime?: number;
  showEstimate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showEstimate: true
});

const isPending = computed(() => props.status === 'pending');

const showPosition = computed(() => 
  props.position !== null && (isPending.value || props.status === 'ready')
);

const showWaitTime = computed(() => 
  props.showEstimate && 
  isPending.value && 
  props.estimatedWaitTime && 
  props.estimatedWaitTime > 0
);

const positionColor = computed(() => {
  if (!isPending.value) return 'success';
  if (props.position <= 3) return 'primary';
  if (props.position <= 5) return 'warning';
  return 'medium';
});

const { t } = useI18n();

const positionText = computed(() => {
  if (!isPending.value) return t('reservations.status.ready');
  return t('reservations.queuePosition') + ': ' + props.position;
});

const { rtlClass } = useRtl();
</script>

<style scoped>
.queue-position-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.queue-position {
  display: flex;
  align-items: center;
}

.position-chip {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 500;
}

.wait-time {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

ion-note {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

ion-icon {
  font-size: 1rem;
}
</style>
