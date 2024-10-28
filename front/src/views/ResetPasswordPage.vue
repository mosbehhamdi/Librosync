<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Reset Password</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <form @submit.prevent="handleSubmit">
        <ion-item>
          <ion-label position="floating">New Password</ion-label>
          <ion-input
            type="password"
            v-model="password"
            required
            :class="{ 'ion-invalid': errors.password }"
          ></ion-input>
          <ion-note slot="error" v-if="errors.password">{{ errors.password }}</ion-note>
        </ion-item>

        <ion-item>
          <ion-label position="floating">Confirm Password</ion-label>
          <ion-input
            type="password"
            v-model="password_confirmation"
            required
            :class="{ 'ion-invalid': errors.password_confirmation }"
          ></ion-input>
          <ion-note slot="error" v-if="errors.password_confirmation">
            {{ errors.password_confirmation }}
          </ion-note>
        </ion-item>

        <ion-button
          expand="block"
          type="submit"
          class="ion-margin-top"
          :disabled="isLoading"
        >
          <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
          <span v-else>Reset Password</span>
        </ion-button>
      </form>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, 
         IonItem, IonLabel, IonInput, IonButton, IonSpinner, 
         IonNote, toastController } from '@ionic/vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const token = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const errors = ref({});
const isLoading = ref(false);

onMounted(() => {
  token.value = route.query.token as string;
  email.value = route.query.email as string;

  if (!token.value || !email.value) {
    router.push('/login');
  }
});

const handleSubmit = async () => {
  try {
    isLoading.value = true;
    errors.value = {};
    await authStore.resetPassword(
      token.value,
      email.value,
      password.value,
      password_confirmation.value
    );
    await presentToast({
      message: 'Password has been reset successfully',
      duration: 3000,
      color: 'success'
    });
    router.push('/login');
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

const presentToast = async (message: string, color: 'success' | 'danger' | 'warning' = 'danger') => {
  const toast = await toastController.create({
    message,
    duration: 3000,
    color
  });
  await toast.present();
};
</script>
