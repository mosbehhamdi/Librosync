<template>
    <ion-page :class="rtlClass">
      <ion-header :class="[rtlClass, 'ion-no-border']">
        <ion-toolbar>
          <ion-buttons slot="start">
            <ion-menu-button class="md:hidden"></ion-menu-button>
          </ion-buttons>
          <ion-title>{{ t(pageTitle) }}</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="confirmLogout" :disabled="isLoading">
              <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
              <span v-else>{{ t('common.actions.logout') }}</span>
            </ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
  
      <ion-menu content-id="main-content" class="md:hidden" :side="isRtl ? 'start' : 'end'">
        <ion-header>
          <ion-toolbar>
            <ion-title>{{ t(isAdmin ? 'menu.adminMenu' : 'menu.title') }}</ion-title>
          </ion-toolbar>
        </ion-header>
  
        <ion-content class="sidebar-content">
          <ion-list>
            <ion-menu-toggle auto-hide="true" :breakpoint="768">
              <!-- Admin Menu Items -->
              <template v-if="isAdmin">
                <ion-item 
                  router-link="/admin/dashboard" 
                  :class="{ 'selected-item': $route.path === '/admin/dashboard' }"
                  lines="none"
                >
                  <ion-icon :icon="gridOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  {{ t('menu.adminDashboard') }}
                </ion-item>
                <ion-item 
                  router-link="/admin/books" 
                  :class="{ 'selected-item': $route.path === '/admin/books' }"
                  lines="none"
                >
                  <ion-icon :icon="libraryOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  {{ t('menu.bookManagement') }}
                </ion-item>
                <ion-item 
                  router-link="/admin/reservations" 
                  :class="{ 'selected-item': $route.path === '/admin/reservations' }"
                  lines="none"
                >
                  <ion-icon :icon="bookmarkOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  {{ t('menu.reservationManagement') }}
                </ion-item>
                <ion-item 
                  router-link="/admin/users" 
                  :class="{ 'selected-item': $route.path === '/admin/users' }"
                  lines="none"
                >
                  <ion-icon :icon="peopleOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  {{ t('menu.userManagement') }}
                </ion-item>
                <ion-item router-link="/settings" lines="none">
                  <ion-icon :icon="settingsOutline" slot="start"></ion-icon>
                  <ion-label>{{ t('settings.title') }}</ion-label>
                </ion-item>
              </template>
  
              <!-- User Menu Items -->
              <template v-else>
                <ion-item router-link="/books" :class="{ 'selected-item': $route.path === '/books' }">
                  <ion-icon :icon="libraryOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  <ion-label>{{ t('menu.browseBooks') }}</ion-label>
                </ion-item>
                <ion-item router-link="/my-reservations" :class="{ 'selected-item': $route.path === '/my-reservations' }">
                  <ion-icon :icon="bookmarkOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  <ion-label>{{ t('menu.myReservations') }}</ion-label>
                </ion-item>
                <ion-item router-link="/my-profile" :class="{ 'selected-item': $route.path === '/my-profile' }">
                  <ion-icon :icon="personOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  <ion-label>{{ t('menu.myProfile') }}</ion-label>
                </ion-item>
                <ion-item router-link="/settings" :class="{ 'selected-item': $route.path === '/settings' }">
                  <ion-icon :icon="settingsOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  <ion-label>{{ t('menu.settings') }}</ion-label>
                </ion-item>
              </template>
            </ion-menu-toggle>
          </ion-list>
        </ion-content>
      </ion-menu>
  
      <div class="flex h-full">
        <!-- Desktop Sidebar -->
        <div :class="[
          'hidden md:block w-64 bg-light',
          isRtl ? 'border-l' : 'border-r'
        ]">
          <div class="p-4">
            <h2 class="text-lg font-semibold mb-4">{{ t(isAdmin ? 'menu.adminMenu' : 'menu.title') }}</h2>
            <ion-list>
              <!-- Admin Desktop Menu Items -->
              <template v-if="isAdmin">
                <ion-item 
                  router-link="/admin/dashboard" 
                  :class="{ 'selected-item': $route.path === '/admin/dashboard' }"
                  lines="none"
                >
                  <ion-icon :icon="gridOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  {{ t('menu.adminDashboard') }}
                </ion-item>
                <ion-item 
                  router-link="/admin/books" 
                  :class="{ 'selected-item': $route.path === '/admin/books' }"
                  lines="none"
                >
                  <ion-icon :icon="libraryOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  {{ t('menu.bookManagement') }}
                </ion-item>
                <ion-item 
                  router-link="/admin/reservations" 
                  :class="{ 'selected-item': $route.path === '/admin/reservations' }"
                  lines="none"
                >
                  <ion-icon :icon="bookmarkOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  {{ t('menu.reservationManagement') }}
                </ion-item>
                <ion-item 
                  router-link="/admin/users" 
                  :class="{ 'selected-item': $route.path === '/admin/users' }"
                  lines="none"
                >
                  <ion-icon :icon="peopleOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  {{ t('menu.userManagement') }}
                </ion-item>
                <ion-item router-link="/settings" lines="none">
                  <ion-icon :icon="settingsOutline" slot="start"></ion-icon>
                  <ion-label>{{ t('settings.title') }}</ion-label>
                </ion-item>
              </template>
  
              <!-- User Desktop Menu Items -->
              <template v-else>
                <ion-item router-link="/books" :class="{ 'selected-item': $route.path === '/books' }">
                  <ion-icon :icon="libraryOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  <ion-label>{{ t('menu.browseBooks') }}</ion-label>
                </ion-item>
                <ion-item router-link="/my-reservations" :class="{ 'selected-item': $route.path === '/my-reservations' }">
                  <ion-icon :icon="bookmarkOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  <ion-label>{{ t('menu.myReservations') }}</ion-label>
                </ion-item>
                <ion-item router-link="/my-profile" :class="{ 'selected-item': $route.path === '/my-profile' }">
                  <ion-icon :icon="personOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  <ion-label>{{ t('menu.myProfile') }}</ion-label>
                </ion-item>
                <ion-item router-link="/settings" :class="{ 'selected-item': $route.path === '/settings' }">
                  <ion-icon :icon="settingsOutline" :slot="isRtl ? 'end' : 'start'"></ion-icon>
                  <ion-label>{{ t('menu.settings') }}</ion-label>
                </ion-item>
              </template>
            </ion-list>
          </div>
        </div>
  
        <div 
          class="flex-1 overflow-hidden" 
          id="main-content"
          :class="{ 'rtl-content': isRtl }"
        >
          <slot></slot>
        </div>
      </div>
    </ion-page>
  </template>
  
  <script setup lang="ts">
  import { computed, ref } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import { useAuthStore } from '@/stores/auth';
  import { useI18n } from 'vue-i18n';
  import { useRtl } from '@/composables/useRtl';
  import {
    IonPage, IonHeader, IonToolbar, IonTitle,
    IonContent, IonMenu, IonList, IonItem, IonLabel,
    IonButtons, IonButton, IonMenuButton, IonIcon,
    IonMenuToggle, IonSpinner
  } from '@ionic/vue';
  import {
    libraryOutline, bookmarkOutline, personOutline,
    gridOutline, peopleOutline, logOutOutline, settingsOutline
  } from 'ionicons/icons';
  
  const route = useRoute();
  const router = useRouter();
  const authStore = useAuthStore();
  const { t } = useI18n();
  const { isRtl, rtlClass } = useRtl();
  const isLoading = ref(false);
  
  const isAdmin = computed(() => authStore.user?.is_admin);
  
  // Dynamic page title based on route
  const pageTitle = computed(() => {
    if (isAdmin.value) {
      const adminTitles = {
        '/admin/dashboard': 'menu.adminDashboard',
        '/admin/books': 'menu.bookManagement',
        '/admin/reservations': 'menu.reservationManagement',
        '/admin/users': 'menu.userManagement'
      };
      return adminTitles[route.path] || 'menu.adminPanel';
    } else {
      const userTitles = {
        '/books': 'menu.browseBooks',
        '/my-reservations': 'menu.myReservations',
        '/my-profile': 'menu.myProfile',
        '/settings': 'menu.settings'
      };
      return userTitles[route.path] || 'menu.library';
    }
  });
  
  // Logout logic
  const confirmLogout = async () => {
    isLoading.value = true;
    try {
      await authStore.logout();
      await router.push('/login');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      isLoading.value = false;
    }
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
  
  /* RTL specific styles */
  :deep(.rtl-mode) {
    direction: rtl;
    text-align: right;
  }
  
  :deep(.rtl-mode) ion-item ion-icon[slot="end"] {
    margin-left: 0;
    margin-right: 8px;
  }
  
  :deep(.rtl-mode) ion-item ion-icon[slot="start"] {
    margin-right: 0;
    margin-left: 8px;
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
  
    .rtl-mode .ion-page#main-content {
      margin-left: 0;
      margin-right: 256px;
    }
  }
  
  /* Mobile styles */
  @media (max-width: 767px) {
    ion-menu {
      --width: 256px;
    }
  }
  
  .rtl-content {
    direction: rtl;
  }
  
  /* Add these styles to your existing styles */
  :deep(.rtl-mode) ion-toolbar {
    direction: rtl;
  }
  
  :deep(.rtl-mode) ion-title {
    text-align: right;
    max-width: fit-content;
  }
  
  :deep(:not(.rtl-mode)) ion-title {
    text-align: left;
    max-width: fit-content;
  }
  
  :deep(ion-title[slot]) {
    padding: 0 16px;
  }
  </style>