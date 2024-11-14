<template>
  <ion-content class="ion-padding" :page-title="t('admin.dashboard.title')">
    <h1 class="text-2xl font-bold mb-6">{{ t('admin.dashboard.title') }}</h1>
    
    <ion-grid>
      <ion-row>
        <ion-col size="12" size-md="4">
          <ion-card>
            <ion-card-header>
              <ion-card-title>{{ t('admin.dashboard.totalUsers') }}</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <h2 class="text-3xl font-bold">{{ adminStore.stats.total_users }}</h2>
            </ion-card-content>
          </ion-card>
        </ion-col>
        
        <ion-col size="12" size-md="4">
          <ion-card>
            <ion-card-header>
              <ion-card-title>{{ t('admin.dashboard.verifiedUsers') }}</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <h2 class="text-3xl font-bold">{{ adminStore.stats.verified_users }}</h2>
            </ion-card-content>
          </ion-card>
        </ion-col>
        
        <ion-col size="12" size-md="4">
          <ion-card>
            <ion-card-header>
              <ion-card-title>{{ t('admin.dashboard.unverifiedUsers') }}</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <h2 class="text-3xl font-bold">{{ adminStore.stats.unverified_users }}</h2>
            </ion-card-content>
          </ion-card>
        </ion-col>
      </ion-row>
    </ion-grid>
  </ion-content>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useAdminStore } from '@/stores/admin';
import { onMounted } from 'vue';
import { 
  IonContent, IonGrid, IonRow, IonCol,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent 
} from '@ionic/vue';

const { t } = useI18n();
const adminStore = useAdminStore();

onMounted(async () => {
  await adminStore.fetchDashboardStats();
});
</script> 