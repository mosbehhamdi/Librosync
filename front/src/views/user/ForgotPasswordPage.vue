<template>
  <ion-page>
    <ion-content class="ion-padding">
      <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-6 bg-white rounded-xl shadow-lg">
          <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">{{ t('auth.forgotPassword.title') }}</h2>
            <p class="mt-2 text-sm text-gray-600">
              {{ t('auth.forgotPassword.description') }}
            </p>
          </div>

          <!-- Email Form -->
          <form v-if="!codeSent" @submit.prevent="handleSendCode" class="mt-8 space-y-6">
            <div>
              <label for="email" class="sr-only">{{ t('auth.fields.email') }}</label>
              <input
                id="email"
                v-model="email"
                type="email"
                required
                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                :placeholder="t('auth.fields.emailPlaceholder')"
              />
            </div>

            <div>
              <button
                type="submit"
                :disabled="isLoading"
                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
                <span v-else>{{ t('auth.forgotPassword.sendCode') }}</span>
              </button>
            </div>
          </form>

          <!-- Reset Password Form -->
          <form v-else @submit.prevent="handleResetPassword" class="mt-8 space-y-6">
            <div class="rounded-md shadow-sm space-y-4">
              <div>
                <label for="code" class="sr-only">{{ t('auth.fields.resetCode') }}</label>
                <input
                  id="code"
                  v-model="resetCode"
                  type="text"
                  required
                  class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                  :placeholder="t('auth.fields.resetCodePlaceholder')"
                />
              </div>

              <div>
                <label for="new-password" class="sr-only">{{ t('auth.fields.newPassword') }}</label>
                <input
                  id="new-password"
                  v-model="newPassword"
                  type="password"
                  required
                  class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                  :placeholder="t('auth.fields.newPasswordPlaceholder')"
                />
              </div>

              <div>
                <label for="confirm-password" class="sr-only">{{ t('auth.fields.confirmPassword') }}</label>
                <input
                  id="confirm-password"
                  v-model="confirmPassword"
                  type="password"
                  required
                  class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                  :placeholder="t('auth.fields.confirmPasswordPlaceholder')"
                />
              </div>
            </div>

            <div>
              <button
                type="submit"
                :disabled="isLoading || !isValidReset"
                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
                <span v-else>{{ t('auth.forgotPassword.resetPassword') }}</span>
              </button>
            </div>
          </form>

          <div class="text-center">
            <router-link to="/login" class="text-indigo-600 hover:text-indigo-500">
              {{ t('auth.forgotPassword.backToLogin') }}
            </router-link>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { IonPage, IonContent, IonSpinner, toastController } from '@ionic/vue';
import { useLanguage } from '@/composables/useLanguage';

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const { getStoredLanguage, setLanguage } = useLanguage();

const email = ref('');
const resetCode = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const isLoading = ref(false);
const codeSent = ref(false);

const isValidReset = computed(() => {
  return resetCode.value &&
         newPassword.value.length >= 8 &&
         newPassword.value === confirmPassword.value;
});

const presentToast = async (message: string, color: 'success' | 'danger' = 'danger') => {
  const toast = await toastController.create({
    message: t(message),
    duration: 3000,
    color
  });
  await toast.present();
};

const handleSendCode = async () => {
  if (!email.value) return;

  isLoading.value = true;
  try {
    await authStore.sendResetCode(email.value);
    codeSent.value = true;
    await presentToast('toast.auth.resetCodeSent', 'success');
  } catch (error) {
    await presentToast('toast.auth.resetCodeError');
  } finally {
    isLoading.value = false;
  }
};

const handleResetPassword = async () => {
  if (!isValidReset.value) {
    await presentToast('toast.auth.invalidInput');
    return;
  }

  isLoading.value = true;
  try {
    await authStore.resetPassword({
      email: email.value,
      code: resetCode.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value
    });
    await presentToast('toast.auth.resetSuccess', 'success');
    router.push('/login');
  } catch (error) {
    await presentToast('toast.auth.resetError');
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  const storedLang = getStoredLanguage();
  setLanguage(storedLang);
});
</script>
