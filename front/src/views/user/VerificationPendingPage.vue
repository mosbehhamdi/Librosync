<template>
  <ion-page>

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
import { useToast } from '@/composables/useToast';

const router = useRouter();
const authStore = useAuthStore();
const code = ref('');
const isLoading = ref(false);
const isResending = ref(false);
const { showToast } = useToast();

const verifyCode = async () => {
  if (code.value.length !== 6) return;
  
  isLoading.value = true;
  try {
    await authStore.verifyEmail(code.value);
    await showToast('toast.auth.verificationSuccess', { color: 'success' });
    router.push('/dashboard');
  } catch (error) {
    await showToast('toast.auth.verificationError', { color: 'danger' });
  } finally {
    isLoading.value = false;
  }
};

const resendCode = async () => {
  isResending.value = true;
  try {
    await authStore.resendVerification();
    await showToast('toast.auth.codeSent', { color: 'success' });
  } catch (error) {
    await showToast('toast.auth.sendError', { color: 'danger' });
  } finally {
    isResending.value = false;
  }
};
</script> 