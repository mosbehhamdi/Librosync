<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>{{ t('user.dashboard.title') }}</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="confirmLogout" :disabled="isLoading">
            <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
            <span v-else>{{ t('common.actions.logout') }}</span>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="max-w-4xl mx-auto space-y-6">
        <!-- Welcome Section -->
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h2 class="text-2xl font-bold text-gray-800 mb-2">
            {{ t('user.dashboard.welcome', { name: authStore.user?.name }) }}
          </h2>
          <p class="text-gray-600">
            {{ t('user.dashboard.description') }}
          </p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Books Reading</h3>
            <p class="text-3xl font-bold text-indigo-600">0</p>
          </div>
          <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Books Delivered</h3>
            <p class="text-3xl font-bold text-green-600">0</p>
          </div>
          <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Reading Time</h3>
            <p class="text-3xl font-bold text-blue-600">0h</p>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Recent Activity</h3>
          <div class="text-gray-600 text-center py-8">
            <p>No recent activity to show.</p>
            <button class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
              Add a Book
            </button>
          </div>
        </div>
      </div>
    </ion-content>

    <!-- Confirmation Alert -->
    <ion-alert
      :is-open="showConfirmation"
      header="Confirm Logout"
      message="Are you sure you want to logout?"
      :buttons="[
        {
          text: 'Cancel',
          role: 'cancel',
        },
        {
          text: 'Logout',
          role: 'confirm',
          handler: handleLogout
        }
      ]"
      @didDismiss="showConfirmation = false"
    ></ion-alert>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonButton,
  IonSpinner,
  IonAlert,
  toastController
} from '@ionic/vue';
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';

const authStore = useAuthStore();
const router = useRouter();
const isLoading = ref(false);
const showConfirmation = ref(false);
const { t } = useI18n();

const confirmLogout = () => {
  showConfirmation.value = true;
};

const presentToast = async (message: string, color: 'success' | 'danger' = 'success') => {
  const toast = await toastController.create({
    message: t(message),
    duration: 2000,
    color,
    position: 'top'
  });
  await toast.present();
};

const handleLogout = async () => {
  isLoading.value = true;
  try {
    await authStore.logout();
    await presentToast('auth.logout.success');
    // Router navigation is handled in the auth store
  } catch (error) {
    console.error('Logout error:', error);
    await presentToast('common.messages.error', 'danger');
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
