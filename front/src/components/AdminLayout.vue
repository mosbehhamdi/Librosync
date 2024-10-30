<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button class="md:hidden"></ion-menu-button>
        </ion-buttons>
        <ion-title>Admin Dashboard</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="logout">
            <ion-icon :icon="logOutOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-menu content-id="main-content" class="md:hidden">
        <ion-header>
          <ion-toolbar>
            <ion-title>Admin Menu</ion-title>
          </ion-toolbar>
        </ion-header>

        <ion-content class="sidebar-content">
          <ion-list>
            <ion-menu-toggle auto-hide="true" :breakpoint="768">
              <ion-item 
                router-link="/admin/dashboard" 
                :class="{ 'selected-item': $route.path === '/admin/dashboard' }"
                lines="none"
              >
                <ion-icon :icon="gridOutline" slot="start"></ion-icon>
                Dashboard
              </ion-item>
              <ion-item 
                router-link="/admin/users" 
                :class="{ 'selected-item': $route.path === '/admin/users' }"
                lines="none"
              >
                <ion-icon :icon="peopleOutline" slot="start"></ion-icon>
                Users
              </ion-item>
            </ion-menu-toggle>
          </ion-list>
        </ion-content>
      </ion-menu>

      <div class="flex h-full">
        <div class="hidden md:block w-64 bg-light border-r">
          <div class="p-4">
            <h2 class="text-lg font-semibold mb-4">Admin Menu</h2>
            <ion-list>
              <ion-item 
                router-link="/admin/dashboard" 
                :class="{ 'selected-item': $route.path === '/admin/dashboard' }"
                lines="none"
              >
                <ion-icon :icon="gridOutline" slot="start"></ion-icon>
                Dashboard
              </ion-item>
              <ion-item 
                router-link="/admin/users" 
                :class="{ 'selected-item': $route.path === '/admin/users' }"
                lines="none"
              >
                <ion-icon :icon="peopleOutline" slot="start"></ion-icon>
                Users
              </ion-item>
            </ion-list>
          </div>
        </div>

        <div class="flex-1 overflow-hidden" id="main-content">
          <slot></slot>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { 
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, 
  IonMenu, IonList, IonItem, IonIcon, IonButtons, 
  IonButton, IonMenuButton, IonMenuToggle
} from '@ionic/vue';
import { gridOutline, peopleOutline, logOutOutline } from 'ionicons/icons';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { ref, onMounted, onUnmounted } from 'vue';

const router = useRouter();
const authStore = useAuthStore();
const isLargeScreen = ref(window.innerWidth >= 768);

const handleResize = () => {
  isLargeScreen.value = window.innerWidth >= 768;
};

onMounted(() => {
  window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<style scoped>
.selected-item {
  --background: var(--ion-color-light-shade);
  --color: var(--ion-color-primary);
}

ion-menu ion-content {
  --background: var(--ion-color-light);
}

ion-menu ion-item, .sidebar-content ion-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --min-height: 50px;
  margin: 4px 8px;
  border-radius: 8px;
  cursor: pointer;
}

ion-menu ion-item ion-icon, .sidebar-content ion-item ion-icon {
  margin-right: 8px;
  color: var(--ion-color-medium);
}

/* Large screen styles */
@media (min-width: 768px) {
  ion-menu {
    width: 256px !important;
    max-width: 256px !important;
  }
  
  .ion-page#main-content {
    margin-left: 256px;
  }
}

/* Mobile styles */
@media (max-width: 767px) {
  ion-menu {
    --width: 256px;
  }
}
</style> 