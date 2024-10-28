<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/login"></ion-back-button>
        </ion-buttons>
        <ion-title>Forgot Password</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <form @submit.prevent="handleSubmit">
        <ion-item>
          <ion-label position="floating">Email</ion-label>
          <ion-input
            type="email"
            v-model="email"
            required
            :class="{ 'ion-invalid': errors.email }"
          ></ion-input>
          <ion-note slot="error" v-if="errors.email">{{ errors.email }}</ion-note>
        </ion-item>

        <ion-button
          expand="block"
          type="submit"
          class="ion-margin-top"
          :disabled="isLoading"
        >
          <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
          <span v-else>Send Reset Link</span>
        </ion-button>
      </form>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonItem, 
         IonLabel, IonInput, IonButton, IonSpinner, IonButtons, 
         IonBackButton, IonNote, toastController } from '@ionic/vue';

const authStore = useAuthStore();
const email = ref('');
const errors = ref({});
const isLoading = ref(false);

const presentToast = async (message: string, color: 'success' | 'danger' | 'warning' = 'danger') => {
  const toast = await toastController.create({
    message,
    duration: 3000,
    color
  });
  await toast.present();
};

const handleSubmit = async () => {
  try {
    isLoading.value = true;
    errors.value = {};
    await authStore.forgotPassword(email.value);
    await presentToast({
      message: 'Password reset link has been sent to your email',
      duration: 3000,
      color: 'success'
    });
    email.value = '';
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      await presentToast({
        message: error.response?.data?.message || 'An error occurred',
        duration: 3000,
        color: 'danger'
      });
    }
  } finally {
    isLoading.value = false;
  }
};
</script>
