<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Reset Password</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="max-w-md mx-auto">
        <div v-if="step === 1">
          <h2 class="text-xl font-semibold mb-4">Forgot Password</h2>
          <form @submit.prevent="sendResetCode">
            <ion-item>
              <ion-label position="floating">Email</ion-label>
              <ion-input
                v-model="email"
                type="email"
                required
              ></ion-input>
            </ion-item>
            
            <ion-button
              type="submit"
              expand="block"
              class="mt-4"
              :disabled="isLoading"
            >
              <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
              <span v-else>Send Reset Code</span>
            </ion-button>
          </form>
        </div>

        <div v-if="step === 2">
          <h2 class="text-xl font-semibold mb-4">Enter Reset Code</h2>
          <form @submit.prevent="verifyCode">
            <ion-item>
              <ion-label position="floating">Code</ion-label>
              <ion-input
                v-model="code"
                type="text"
                maxlength="6"
                required
              ></ion-input>
            </ion-item>
            
            <ion-button
              type="submit"
              expand="block"
              class="mt-4"
              :disabled="isLoading"
            >
              <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
              <span v-else>Verify Code</span>
            </ion-button>
          </form>
        </div>

        <div v-if="step === 3">
          <h2 class="text-xl font-semibold mb-4">Set New Password</h2>
          <form @submit.prevent="resetPassword">
            <ion-item>
              <ion-label position="floating">New Password</ion-label>
              <ion-input
                v-model="password"
                type="password"
                required
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="floating">Confirm Password</ion-label>
              <ion-input
                v-model="passwordConfirmation"
                type="password"
                required
              ></ion-input>
            </ion-item>
            
            <ion-button
              type="submit"
              expand="block"
              class="mt-4"
              :disabled="isLoading"
            >
              <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
              <span v-else>Reset Password</span>
            </ion-button>
          </form>
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
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonItem,
  IonLabel,
  IonInput,
  IonButton,
  IonSpinner,
  toastController
} from '@ionic/vue';

const router = useRouter();
const authStore = useAuthStore();

const step = ref(1);
const isLoading = ref(false);
const email = ref('');
const code = ref('');
const token = ref('');
const password = ref('');
const passwordConfirmation = ref('');

const sendResetCode = async () => {
  isLoading.value = true;
  try {
    await authStore.forgotPassword(email.value);
    step.value = 2;
    const toast = await toastController.create({
      message: 'Reset code sent to your email',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
  } catch (error) {
    const toast = await toastController.create({
      message: 'Failed to send reset code',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isLoading.value = false;
  }
};

const verifyCode = async () => {
  isLoading.value = true;
  try {
    const response = await authStore.verifyResetCode(email.value, code.value);
    console.log('Verification response:', response);
    
    if (response.token) {
      token.value = response.token;
      step.value = 3;
      const toast = await toastController.create({
        message: 'Code verified successfully',
        duration: 2000,
        color: 'success'
      });
      await toast.present();
    } else {
      throw new Error('No token received');
    }
  } catch (error: any) {
    console.error('Verification error:', error);
    const message = error.response?.data?.message || 'Invalid or expired code';
    const toast = await toastController.create({
      message,
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isLoading.value = false;
  }
};

const resetPassword = async () => {
  if (password.value !== passwordConfirmation.value) {
    const toast = await toastController.create({
      message: 'Passwords do not match',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
    return;
  }

  if (!token.value) {
    const toast = await toastController.create({
      message: 'Invalid reset token. Please try again.',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
    return;
  }

  isLoading.value = true;
  try {
    console.log('Resetting password with data:', {
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    });

    await authStore.resetPassword({
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    });
    
    const toast = await toastController.create({
      message: 'Password reset successfully',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
    
    router.push('/login');
  } catch (error: any) {
    console.error('Reset password error:', error);
    const message = error.response?.data?.message || 'Failed to reset password';
    const toast = await toastController.create({
      message,
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isLoading.value = false;
  }
};
</script>
