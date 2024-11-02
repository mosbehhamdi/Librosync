<template>
  <ion-content class="ion-padding">
    <div class="max-w-2xl mx-auto">
      <ion-card>
        <ion-card-header>
          <ion-card-title>My Profile</ion-card-title>
        </ion-card-header>

        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-label position="stacked">Name</ion-label>
              <ion-input
                v-model="profileData.name"
                :readonly="!isEditing"
                type="text"
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Email</ion-label>
              <ion-input
                v-model="profileData.email"
                readonly
                type="email"
              ></ion-input>
            </ion-item>

            <ion-item v-if="isEditing">
              <ion-label position="stacked">Current Password</ion-label>
              <ion-input
                v-model="profileData.current_password"
                type="password"
                required
              ></ion-input>
            </ion-item>

            <ion-item v-if="isEditing">
              <ion-label position="stacked">New Password</ion-label>
              <ion-input
                v-model="profileData.password"
                type="password"
                placeholder="Leave blank to keep current password"
              ></ion-input>
            </ion-item>

            <ion-item v-if="isEditing">
              <ion-label position="stacked">Confirm New Password</ion-label>
              <ion-input
                v-model="profileData.password_confirmation"
                type="password"
              ></ion-input>
            </ion-item>
          </ion-list>

          <div class="ion-padding-top">
            <ion-button
              v-if="!isEditing"
              expand="block"
              @click="startEditing"
            >
              Edit Profile
            </ion-button>

            <ion-button
              v-if="isEditing"
              expand="block"
              color="primary"
              @click="validateForm() && saveProfile()"
              :disabled="isSaving"
            >
              <ion-spinner v-if="isSaving" name="crescent"></ion-spinner>
              <span v-else>Save Changes</span>
            </ion-button>

            <ion-button
              v-if="isEditing"
              expand="block"
              fill="clear"
              @click="cancelEditing"
            >
              Cancel
            </ion-button>
          </div>
        </ion-card-content>
      </ion-card>

      <!-- Statistics Card -->
      <ion-card class="mt-4">
        <ion-card-header>
          <ion-card-title>My Statistics</ion-card-title>
        </ion-card-header>

        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-label>
                <h2>Active Reservations</h2>
                <p>{{ stats.activeReservations || 0 }}</p>
              </ion-label>
            </ion-item>
            <ion-item>
              <ion-label>
                <h2>Total Books Reserved</h2>
                <p>{{ stats.totalReservations || 0 }}</p>
              </ion-label>
            </ion-item>
          </ion-list>
        </ion-card-content>
      </ion-card>
    </div>
  </ion-content>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { toastController } from '@ionic/vue';
import {
  IonContent,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonList,
  IonItem,
  IonLabel,
  IonInput,
  IonButton,
  IonSpinner
} from '@ionic/vue';

const authStore = useAuthStore();
const isEditing = ref(false);
const isSaving = ref(false);

const profileData = ref({
  name: authStore.user?.name || '',
  email: authStore.user?.email || '',
  current_password: '',
  password: '',
  password_confirmation: ''
});

const stats = ref({
  activeReservations: 0,
  totalReservations: 0
});

const presentToast = async (message: string, color: 'success' | 'danger' = 'success') => {
  const toast = await toastController.create({
    message,
    duration: 3000,
    color,
    position: 'bottom'
  });
  await toast.present();
};

const startEditing = () => {
  isEditing.value = true;
};

const cancelEditing = () => {
  isEditing.value = false;
  profileData.value.password = '';
  profileData.value.current_password = '';
  profileData.value.password_confirmation = '';
  profileData.value.name = authStore.user?.name || '';
};

const saveProfile = async () => {
  if (isSaving.value) return;

  try {
    isSaving.value = true;
    
    // First handle profile update
    const updateData = {
      name: profileData.value.name,
      email: profileData.value.email
    };

    await authStore.updateProfile(updateData);

    // Then handle password update if provided
    if (profileData.value.password) {
      if (!profileData.value.current_password) {
        await presentToast('Current password is required to change password', 'danger');
        return;
      }
      
      await authStore.updatePassword({
        current_password: profileData.value.current_password,
        password: profileData.value.password,
        password_confirmation: profileData.value.password_confirmation
      });
    }

    isEditing.value = false;
    await presentToast('Profile updated successfully', 'success');
  } catch (error: any) {
    console.error('Profile update error:', error);
    await presentToast(
      error.response?.data?.message || 
      error.response?.data?.errors?.current_password?.[0] ||
      'Error updating profile', 
      'danger'
    );
  } finally {
    isSaving.value = false;
  }
};

// Add validation before save
const validateForm = () => {
  if (!profileData.value.name.trim()) {
    presentToast('Name is required', 'danger');
    return false;
  }
  
  if (profileData.value.password && profileData.value.password.length < 8) {
    presentToast('Password must be at least 8 characters', 'danger');
    return false;
  }
  
  if (profileData.value.password !== profileData.value.password_confirmation) {
    presentToast('Passwords do not match', 'danger');
    return false;
  }
  
  return true;
};

onMounted(() => {
  if (authStore.user) {
    profileData.value.name = authStore.user.name;
    profileData.value.email = authStore.user.email;
  }
});
</script> 