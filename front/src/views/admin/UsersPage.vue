<template>
  <admin-layout>
    <ion-content class="ion-padding">
      <h1>User Management</h1>
      
      <!-- Search and Filter -->
      <search-filter 
        :initialSearch="filters.search" 
        label="Search users..."
        @search="handleSearch" 
      />

      <ion-list>
        <ion-item v-for="user in filteredUsers" :key="user.id">
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
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useAdminStore } from '@/stores/admin';
import { IonContent, IonList, IonItem, IonLabel, 
         IonButton, alertController } from '@ionic/vue';
import SearchFilter from '@/components/admin/SearchFilter.vue'; // Import the SearchFilter component

const adminStore = useAdminStore();

const filters = ref({
  search: ''
});

// Fetch users on mount
onMounted(async () => {
  await adminStore.fetchUsers();
});

// Computed property to filter users based on search input
const filteredUsers = computed(() => {
  return adminStore.users.filter(user => 
    user.name.toLowerCase().includes(filters.value.search.toLowerCase()) ||
    user.email.toLowerCase().includes(filters.value.search.toLowerCase())
  );
});

// Handle search input from SearchFilter
const handleSearch = (filterData) => {
  filters.value.search = filterData.search || ''; // Update the search filter
};

// Confirm delete user
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