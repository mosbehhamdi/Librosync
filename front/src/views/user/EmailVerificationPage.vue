<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Email Verification</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="max-w-md mx-auto space-y-4">
        <div v-if="!verified" class="text-center">
          <h2 class="text-xl font-semibold mb-4">Verify Your Email</h2>
          <p class="mb-4">Please check your email for a verification link.</p>
          <ion-button @click="resendVerification" :disabled="isLoading">
            <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
            <span v-else>Resend Verification Email</span>
          </ion-button>
        </div>
        <div v-else class="text-center">
          <h2 class="text-xl font-semibold text-green-600">Email Verified!</h2>
          <p class="mt-4">Thank you for verifying your email.</p>
          <ion-button router-link="/dashboard" class="mt-4">
            Go to Dashboard
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

const route = useRoute();
const authStore = useAuthStore();
const isLoading = ref(false);
const verified = ref(false);

const verifyEmail = async () => {
  try {
    const response = await authStore.verifyEmail(route.params.id as string, route.params.hash as string);
    verified.value = true;
    presentToast('Email verified successfully!', 'success');
  } catch (error) {
    presentToast('Verification failed. Please try again.', 'danger');
  }
};

const resendVerification = async () => {
  isLoading.value = true;
  try {
    await authStore.resendVerification();
    presentToast('Verification email sent!', 'success');
  } catch (error) {
    presentToast('Failed to send verification email.', 'danger');
  } finally {
    isLoading.value = false;
  }
};

const presentToast = async (message: string, color: 'success' | 'danger' = 'danger') => {
  const toast = await toastController.create({
    message,
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