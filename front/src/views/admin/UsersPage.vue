<template>
  <admin-layout>
    <ion-content class="ion-padding">
      <h1>User Management</h1>
      
      <ion-list>
        <ion-item v-for="user in adminStore.users" :key="user.id">
          <ion-label>
            <h2>{{ user.name }}</h2>
            <p>{{ user.email }}</p>
            <p>{{ user.email_verified_at ? 'Verified' : 'Not Verified' }}</p>
          </ion-label>
          
          <ion-button 
            slot="end" 
            color="danger"
            @click="confirmDelete(user)"
          >
            Delete
          </ion-button>
        </ion-item>
      </ion-list>
    </ion-content>
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { useAdminStore } from '@/stores/admin';
import { IonContent, IonList, IonItem, IonLabel, 
         IonButton, alertController } from '@ionic/vue';

const adminStore = useAdminStore();

onMounted(async () => {
  await adminStore.fetchUsers();
});

const confirmDelete = async (user: any) => {
  const alert = await alertController.create({
    header: 'Confirm Delete',
    message: `Are you sure you want to delete ${user.name}?`,
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel'
      },
      {
        text: 'Delete',
        role: 'destructive',
        handler: () => adminStore.deleteUser(user.id)
      }
    ]
  });
  await alert.present();
};
</script> 