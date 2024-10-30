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
              <ion-label position="stacked">New Password</ion-label>
              <ion-input
                v-model="profileData.password"
                type="password"
                placeholder="Leave blank to keep current password"
              ></ion-input>
            </ion-item>

            <ion-item v-if="isEditing">
              <ion-label position="stacked">Confirm Password</ion-label>
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
              @click="saveProfile"
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
  password: '',
  password_confirmation: ''
});

const stats = ref({
  activeReservations: 0,
  totalReservations: 0
});

const startEditing = () => {
  isEditing.value = true;
};

const cancelEditing = () => {
  isEditing.value = false;
  profileData.value.password = '';
  profileData.value.password_confirmation = '';
  // Reset name to original value
  profileData.value.name = authStore.user?.name || '';
};

const saveProfile = async () => {
  if (isSaving.value) return;

  try {
    isSaving.value = true;
    
    const updateData: any = {
      name: profileData.value.name
    };

    if (profileData.value.password) {
      updateData.password = profileData.value.password;
      updateData.password_confirmation = profileData.value.password_confirmation;
    }

    await authStore.updateProfile(updateData);

    const toast = await toastController.create({
      message: 'Profile updated successfully',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
  } catch (error: any) {
    const toast = await toastController.create({
      message: 'Failed to update profile',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isSaving.value = false;
  }
};
</script> 