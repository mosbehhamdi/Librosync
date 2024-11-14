<template>
  <ion-page>
    <ion-content class="ion-padding">
      <div class="max-w-md mx-auto space-y-4">
        <div v-if="!verified" class="text-center">
          <h2 class="text-xl font-semibold mb-4">{{ t('auth.verification.title') }}</h2>
          <p class="mb-4">{{ t('auth.verification.checkEmail') }}</p>
          <ion-button @click="resendVerification" :disabled="isLoading">
            <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
            <span v-else>{{ t('auth.verification.resend') }}</span>
          </ion-button>
        </div>
        <div v-else class="text-center">
          <h2 class="text-xl font-semibold text-green-600">{{ t('auth.verification.success') }}</h2>
          <p class="mt-4">{{ t('auth.verification.thankYou') }}</p>
          <ion-button router-link="/dashboard" class="mt-4">
            {{ t('auth.verification.gotoDashboard') }}
          </ion-button>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButton, IonSpinner, toastController } from '@ionic/vue';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const route = useRoute();
const authStore = useAuthStore();
const isLoading = ref(false);
const verified = ref(false);
const { t } = useI18n();

const verifyEmail = async () => {
  try {
    const response = await authStore.verifyEmail(route.params.id as string, route.params.hash as string);
    verified.value = true;
    presentToast('toast.auth.verificationSuccess', 'success');
  } catch (error) {
    presentToast('toast.auth.verificationError');
  }
};

const resendVerification = async () => {
  isLoading.value = true;
  try {
    await authStore.resendVerification();
    presentToast('toast.auth.codeSent', 'success');
  } catch (error) {
    presentToast('toast.auth.sendError');
  } finally {
    isLoading.value = false;
  }
};

const presentToast = async (message: string, color: 'success' | 'danger' = 'danger') => {
  const toast = await toastController.create({
    message: t(message),
    duration: 3000,
    color
  });
  await toast.present();
};

// Verify email if ID and hash are present in URL
if (route.params.id && route.params.hash) {
  verifyEmail();
}
</script> 