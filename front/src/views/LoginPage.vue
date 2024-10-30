<template>
  <ion-page>
    <ion-content class="ion-padding">
      <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-6 bg-white rounded-xl shadow-lg">
          <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Sign in to your account</h2>
          </div>
          <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
            <div class="rounded-md shadow-sm space-y-4">
              <div>
                <label for="email" class="sr-only">Email address</label>
                <input
                  id="email"
                  v-model="email"
                  type="email"
                  required
                  class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                  placeholder="Email address"
                />
              </div>
              <div>
                <label for="password" class="sr-only">Password</label>
                <input
                  id="password"
                  v-model="password"
                  type="password"
                  required
                  class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                  placeholder="Password"
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
                <span v-else>Sign in</span>
              </button>
            </div>
          </form>

          <!-- Add this section for links -->
          <div class="flex flex-col space-y-2 text-center text-sm">
            <div>
              <router-link 
                to="/forgot-password" 
                class="text-indigo-600 hover:text-indigo-500"
              >
                Forgot your password?
              </router-link>
            </div>
            <div>
              <span class="text-gray-500">Don't have an account? </span>
              <router-link 
                to="/register" 
                class="text-indigo-600 hover:text-indigo-500 font-medium"
              >
                Create one now
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonContent, IonSpinner, toastController } from '@ionic/vue';
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const email = ref('');
const password = ref('');
const isLoading = ref(false);
const router = useRouter();
const authStore = useAuthStore();

// Add onMounted to check authentication status
onMounted(() => {
  if (authStore.isAuthenticated) {
    if (authStore.isAdmin) {
      router.replace('/admin/dashboard');
    } else {
      router.replace('/books');
    }
  }
});

const presentToast = async (message: string, color: 'success' | 'danger' | 'warning' = 'danger') => {
  const toast = await toastController.create({
    message,
    duration: 3000,
    color
  });
  await toast.present();
};

const handleLogin = async () => {
  if (!email.value || !password.value) {
    await presentToast('Please fill in all fields', 'warning');
    return;
  }

  isLoading.value = true;
  try {
    await authStore.login(email.value, password.value);
    await presentToast('Login successful!', 'success');
    
    // Redirect based on user role
    if (authStore.user?.is_admin) {
      await router.replace('/admin/dashboard');
    } else {
      await router.replace('/books');
    }
  } catch (error: any) {
    console.error('Login error:', error);
    await presentToast(
      error.response?.data?.message || 
      error.message || 
      'Login failed',
      'danger'
    );
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
