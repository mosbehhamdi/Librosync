<template>
  <admin-layout>
    <ion-content class="ion-padding">
      <h1 class="text-2xl font-bold mb-6">Admin Dashboard</h1>
      
      <ion-grid>
        <ion-row>
          <ion-col size="12" size-md="4">
            <ion-card>
              <ion-card-header>
                <ion-card-title>Total Users</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <h2 class="text-3xl font-bold">{{ adminStore.stats.total_users }}</h2>
              </ion-card-content>
            </ion-card>
          </ion-col>
          
          <ion-col size="12" size-md="4">
            <ion-card>
              <ion-card-header>
                <ion-card-title>Verified Users</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <h2 class="text-3xl font-bold">{{ adminStore.stats.verified_users }}</h2>
              </ion-card-content>
            </ion-card>
          </ion-col>
          
          <ion-col size="12" size-md="4">
            <ion-card>
              <ion-card-header>
                <ion-card-title>Unverified Users</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <h2 class="text-3xl font-bold">{{ adminStore.stats.unverified_users }}</h2>
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </admin-layout>
</template>

<script setup lang="ts">
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useAdminStore } from '@/stores/admin';
import { onMounted } from 'vue';
import { 
  IonContent, IonGrid, IonRow, IonCol,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonButton 
} from '@ionic/vue';

const adminStore = useAdminStore();

onMounted(async () => {
  await adminStore.fetchDashboardStats();
});
</script> 