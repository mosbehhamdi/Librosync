<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title>{{ pageTitle }}</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="confirmLogout" :disabled="isLoading">
            <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
            <span v-else>Logout</span>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-menu content-id="main-content" type="overlay">
      <ion-header>
        <ion-toolbar>
          <ion-title>Menu</ion-title>
        </ion-toolbar>
      </ion-header>
      
      <ion-content>
        <ion-list>
          <ion-menu-toggle auto-hide="true">
            <ion-item router-link="/books" :class="{ 'selected-item': $route.path === '/books' }">
              <ion-icon :icon="libraryOutline" slot="start"></ion-icon>
              <ion-label>Browse Books</ion-label>
            </ion-item>
            <ion-item router-link="/my-reservations" :class="{ 'selected-item': $route.path === '/my-reservations' }">
              <ion-icon :icon="bookmarkOutline" slot="start"></ion-icon>
              <ion-label>My Reservations</ion-label>
            </ion-item>
            <ion-item router-link="/my-profile" :class="{ 'selected-item': $route.path === '/my-profile' }">
              <ion-icon :icon="personOutline" slot="start"></ion-icon>
              <ion-label>My Profile</ion-label>
            </ion-item>
          </ion-menu-toggle>
        </ion-list>
      </ion-content>
    </ion-menu>

    <ion-content id="main-content">
      <slot></slot>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import {
  IonPage, IonHeader, IonToolbar, IonTitle,
  IonContent, IonMenu, IonList, IonItem, IonLabel,
  IonButtons, IonButton, IonMenuButton, IonIcon,
  IonMenuToggle, IonSpinner
} from '@ionic/vue';
import {
  libraryOutline, bookmarkOutline, personOutline,
  logOutOutline
} from 'ionicons/icons';

const route = useRoute();
const authStore = useAuthStore();
const isLoading = ref(false);

// Titre dynamique basé sur la route
const pageTitle = computed(() => {
  const titles = {
    '/books': 'Browse Books',
    '/my-reservations': 'My Reservations',
    '/my-profile': 'My Profile'
  };
  return titles[route.path] || 'Library';
});

// Logique de déconnexion
const confirmLogout = async () => {
  isLoading.value = true;
  try {
    await authStore.logout();
  } catch (error) {
    console.error('Logout error:', error);
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.selected-item {
  --background: var(--ion-color-light);
}
</style> 