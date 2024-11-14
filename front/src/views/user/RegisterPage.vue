<template>
  <ion-page>
    <ion-content class="ion-padding">
      <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-6 bg-white rounded-xl shadow-lg">
          <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">{{ t('auth.register.title') }}</h2>
          </div>
          <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
            <div class="rounded-md shadow-sm space-y-4">
              <!-- Name Input -->
              <div>
                <label for="name" class="sr-only">{{ t('auth.fields.name') }}</label>
                <input
                  id="name"
                  v-model="name"
                  type="text"
                  required
                  class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                  :placeholder="t('auth.fields.namePlaceholder')"
                />
              </div>

              <!-- Email Input -->
              <div>
                <label for="email" class="sr-only">{{ t('auth.fields.email') }}</label>
                <input
                  id="email"
                  v-model="email"
                  type="email"
                  required
                  :class="{
                    'border-red-500': email && !isValidEmail,
                    'appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm': true
                  }"
                  :placeholder="t('auth.fields.emailPlaceholder')"
                />
                <p v-if="email && !isValidEmail" class="mt-1 text-sm text-red-500">
                  {{ t('auth.validation.invalidEmail') }}
                </p>
              </div>

              <!-- Password Input -->
              <div>
                <label for="password" class="sr-only">{{ t('auth.fields.password') }}</label>
                <input
                  id="password"
                  v-model="password"
                  type="password"
                  required
                  :class="{
                    'border-red-500': password && !isValidPassword,
                    'appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm': true
                  }"
                  :placeholder="t('auth.fields.passwordPlaceholder')"
                />
                <p v-if="password && !isValidPassword" class="mt-1 text-sm text-red-500">
                  {{ t('auth.validation.passwordLength') }}
                </p>
              </div>
              <div>
                <label for="password_confirmation" class="sr-only">Confirm Password</label>
                <input
                  id="password_confirmation"
                  v-model="passwordConfirmation"
                  type="password"
                  required
                  class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                  placeholder="Confirm Password"
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
                <span v-else>{{ t('auth.register.submit') }}</span>
              </button>
            </div>
          </form>
          <div class="text-center">
            <router-link to="/login" class="text-indigo-600 hover:text-indigo-500">
              {{ t('auth.register.haveAccount') }}
            </router-link>
          </div>
          <!-- Add validation messages -->
          <div v-if="!passwordsMatch && passwordConfirmation.value" class="text-red-500">
            {{ t('auth.validation.passwordMismatch') }}
          </div>
          <div v-if="password.value && password.value.length < 8" class="text-red-500">
            {{ t('auth.validation.passwordLength') }}
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonContent, IonSpinner } from '@ionic/vue';
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';
import { useLanguage } from '@/composables/useLanguage';

const { t } = useI18n();
const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const isLoading = ref(false);
const router = useRouter();
const authStore = useAuthStore();
const { showToast } = useToast();
const { getStoredLanguage, setLanguage } = useLanguage();

onMounted(() => {
  const storedLang = getStoredLanguage();
  setLanguage(storedLang);
});

// Add form validation with computed properties
const isValidEmail = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email.value);
});

const isValidPassword = computed(() => 
  password.value.length >= 8
);

const passwordsMatch = computed(() => 
  password.value === passwordConfirmation.value
);

const isFormValid = computed(() => 
  name.value &&
  isValidEmail.value &&
  isValidPassword.value &&
  passwordsMatch.value
);

const handleRegister = async () => {
  if (!isFormValid.value) {
    await showToast('auth.validation.checkFields', {
      color: 'warning',
      translate: true
    });
    return;
  }

  isLoading.value = true;
  try {
    await authStore.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    await showToast('auth.register.success', {
      color: 'success', 
      translate: true
    });
    await router.replace('/books');
  } catch (error: any) {
    console.error('Registration error:', error);
    let errorMessage = 'auth.register.error';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.response?.data?.errors) {
      errorMessage = Object.values(error.response.data.errors)
        .flat()
        .join(', ');
    }
    await showToast(errorMessage, {
      color: 'danger',
      translate: !error.response?.data?.message
    });
  } finally {
    isLoading.value = false;
  }
};
</script>
