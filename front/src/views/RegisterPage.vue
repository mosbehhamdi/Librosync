<template>
  <ion-page>
    <ion-content class="ion-padding">
      <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-6 bg-white rounded-xl shadow-lg">
          <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Create your account</h2>
          </div>
          <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
            <div class="rounded-md shadow-sm space-y-4">
              <!-- Name Input -->
              <div>
                <label for="name" class="sr-only">Full name</label>
                <input
                  id="name"
                  v-model="name"
                  type="text"
                  required
                  class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                  placeholder="Full name"
                />
              </div>

              <!-- Email Input -->
              <div>
                <label for="email" class="sr-only">Email address</label>
                <input
                  id="email"
                  v-model="email"
                  type="email"
                  required
                  :class="{
                    'border-red-500': email && !isValidEmail,
                    'appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm': true
                  }"
                  placeholder="Email address"
                />
                <p v-if="email && !isValidEmail" class="mt-1 text-sm text-red-500">
                  Please enter a valid email address
                </p>
              </div>

              <!-- Password Input -->
              <div>
                <label for="password" class="sr-only">Password</label>
                <input
                  id="password"
                  v-model="password"
                  type="password"
                  required
                  :class="{
                    'border-red-500': password && !isValidPassword,
                    'appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm': true
                  }"
                  placeholder="Password"
                />
                <p v-if="password && !isValidPassword" class="mt-1 text-sm text-red-500">
                  Password must be at least
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
                <span v-else>Sign up</span>
              </button>
            </div>
          </form>
          <div class="text-center">
            <router-link to="/login" class="text-indigo-600 hover:text-indigo-500">
              Already have an account? Sign in
            </router-link>
          </div>
          <!-- Add validation messages -->
          <div v-if="!passwordsMatch && passwordConfirmation.value" class="text-red-500">
            Passwords do not match
          </div>
          <div v-if="password.value && password.value.length < 8" class="text-red-500">
            Password must be at least 8 characters
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonContent, IonSpinner, toastController } from '@ionic/vue';
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const isLoading = ref(false);
const router = useRouter();
const authStore = useAuthStore();

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

const presentToast = async (message: string, color: 'success' | 'danger' | 'warning' = 'danger') => {
  const toast = await toastController.create({
    message,
    duration: 3000,
    color
  });
  await toast.present();
};

const handleRegister = async () => {
  if (!isFormValid.value) {
    await presentToast('Please check all fields', 'warning');
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
    await presentToast('Registration successful!', 'success');
    await router.replace('/books');
  } catch (error: any) {
    console.error('Registration error:', error);
    let errorMessage = 'Registration failed';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.response?.data?.errors) {
      errorMessage = Object.values(error.response.data.errors)
        .flat()
        .join(', ');
    } else if (error.message) {
      errorMessage = error.message;
    }
    await presentToast(errorMessage, 'danger');
  } finally {
    isLoading.value = false;
  }
};
</script>
