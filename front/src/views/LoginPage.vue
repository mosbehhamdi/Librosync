<template>
  <ion-page>
    <ion-content class="ion-padding">
      <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-6 bg-white rounded-xl shadow-lg">
          <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">{{ t('auth.login.title') }}</h2>
          </div>
          <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
            <div class="rounded-md shadow-sm space-y-4">
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
                <label for="password" class="sr-only">{{ t('auth.fields.password') }}</label>
                <input
                  id="password"
                  v-model="password"
                  type="password"
                  required
                  class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                  :placeholder="t('auth.fields.passwordPlaceholder')"
                />
              </div>
            </div>

            <div>
              <button
                type="submit"
                :disabled="isLoading"
                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
                <span v-else>{{ t('auth.login.submit') }}</span>
              </button>
            </div>
          </form>

          <div class="flex flex-col space-y-2 text-center text-sm">
            <div>
              <router-link 
                to="/forgot-password" 
                class="text-indigo-600 hover:text-indigo-500"
              >
                {{ t('auth.login.forgotPassword') }}
              </router-link>
            </div>
            <div>
              <span class="text-gray-500">{{ t('auth.login.noAccount') }} </span>
              <router-link 
                to="/register" 
                class="text-indigo-600 hover:text-indigo-500 font-medium"
              >
                {{ t('auth.login.createAccount') }}
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { IonPage, IonContent, IonSpinner } from '@ionic/vue';
import { useToast } from '@/composables/useToast';
import { useLanguage } from '@/composables/useLanguage';

const { t } = useI18n();
const email = ref('');
const password = ref('');
const isLoading = ref(false);
const router = useRouter();
const authStore = useAuthStore();
const { showToast } = useToast();
const { getStoredLanguage, setLanguage } = useLanguage();

onMounted(() => {
  const storedLang = getStoredLanguage();
  setLanguage(storedLang);

  if (authStore.isAuthenticated) {
    if (authStore.isAdmin) {
      router.replace('/admin/dashboard');
    } else {
      router.replace('/books');
    }
  }
});

const handleLogin = async () => {
  if (!email.value || !password.value) {
    await showToast('auth.validation.fillAllFields', { 
      color: 'warning',
      translate: true 
    });
    return;
  }

  isLoading.value = true;
  try {
    await authStore.login({
      email: email.value,
      password: password.value,
    });
    await showToast('auth.login.success', { 
      color: 'success',
      translate: true 
    });
  } catch (error: any) {
    console.error('Login error:', error);
    const response = error.response?.data;
    
    if (response?.error) {
      await showToast(response.error, { 
        color: 'danger',
        translate: false 
      });
    } else if (response?.message) {
      await showToast(response.message, { 
        color: 'danger',
        translate: false
      });
    } else if (response?.errors) {
      const errorMessages = Object.values(response.errors)
        .flat()
        .join(', ');
      await showToast(errorMessages, { 
        color: 'danger',
        translate: false
      });
    } else {
      await showToast(error.message || 'auth.login.error', { 
        color: 'danger',
        translate: !error.message 
      });
    }
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.space-y-2 > :not([hidden]) ~ :not([hidden]) {
  --tw-space-y-reverse: 0;
  margin-top: calc(0.5rem * calc(1 - var(--tw-space-y-reverse)));
  margin-bottom: calc(0.5rem * var(--tw-space-y-reverse));
}
</style>
