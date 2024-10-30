<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Admin Dashboard</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="logout">
            <ion-icon :icon="logOutOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-menu content-id="main-content">
        <ion-header>
          <ion-toolbar>
            <ion-title>Admin Menu</ion-title>
          </ion-toolbar>
        </ion-header>

        <ion-content>
          <ion-list>
            <ion-item router-link="/admin/dashboard">
              <ion-icon :icon="gridOutline" slot="start"></ion-icon>
              Dashboard
            </ion-item>
            <ion-item router-link="/admin/users">
              <ion-icon :icon="peopleOutline" slot="start"></ion-icon>
              Users
            </ion-item>
          </ion-list>
        </ion-content>
      </ion-menu>

      <div class="ion-page" id="main-content">
        <slot></slot>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, 
         IonMenu, IonList, IonItem, IonIcon, IonButtons, IonButton } from '@ionic/vue';
import { gridOutline, peopleOutline, logOutOutline } from 'ionicons/icons';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const router = useRouter();
const authStore = useAuthStore();

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script> 