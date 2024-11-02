<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Email Verification</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="max-w-md mx-auto space-y-4">
        <div class="text-center">
          <h2 class="text-xl font-semibold mb-4">Verify Your Email</h2>
          <p class="mb-4">
            We've sent a verification code to your email address.
            Please enter the code below to verify your account.
          </p>

          <form @submit.prevent="verifyCode" class="space-y-4">
            <ion-item>
              <ion-label position="floating">Verification Code</ion-label>
              <ion-input
                v-model="code"
                type="text"
                maxlength="6"
                placeholder="Enter 6-digit code"
                required
              ></ion-input>
            </ion-item>

            <ion-button type="submit" expand="block" :disabled="isLoading || code.length !== 6">
              <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
              <span v-else>Verify Code</span>
            </ion-button>
          </form>

          <div class="mt-4">
            <ion-button fill="clear" @click="resendCode" :disabled="isResending">
              <ion-spinner v-if="isResending" name="crescent"></ion-spinner>
              <span v-else>Resend Code</span>
            </ion-button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { 
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
  IonButton, IonSpinner, IonItem, IonLabel, IonInput,
  toastController 
} from '@ionic/vue';

const router = useRouter();
const authStore = useAuthStore();
const code = ref('');
const isLoading = ref(false);
const isResending = ref(false);

const verifyCode = async () => {
  if (code.value.length !== 6) return;
  
  isLoading.value = true;
  try {
    await authStore.verifyEmail(code.value);
    const toast = await toastController.create({
      message: 'Email verified successfully!',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
    router.push('/dashboard');
  } catch (error) {
    const toast = await toastController.create({
      message: 'Invalid or expired verification code',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isLoading.value = false;
  }
};

const resendCode = async () => {
  isResending.value = true;
  try {
    await authStore.resendVerification();
    const toast = await toastController.create({
      message: 'New verification code sent!',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
  } catch (error) {
    const toast = await toastController.create({
      message: 'Failed to send verification code',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isResending.value = false;
  }
};
</script> 