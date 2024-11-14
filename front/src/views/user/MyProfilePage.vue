<template>
  <ion-content class="ion-padding">
    <div class="max-w-2xl mx-auto">
      <ion-card>
        <ion-card-header>
          <ion-card-title>{{ t('profile.title') }}</ion-card-title>
        </ion-card-header>

        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-label position="stacked">{{ t('profile.fields.name') }}</ion-label>
              <ion-input
                v-model="profileData.name"
                :readonly="!isEditing"
                type="text"
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">{{ t('profile.fields.email') }}</ion-label>
              <ion-input
                v-model="profileData.email"
                readonly
                type="email"
              ></ion-input>
            </ion-item>

            <ion-item v-if="isEditing">
              <ion-label position="stacked">{{ t('profile.fields.currentPassword') }}</ion-label>
              <ion-input
                v-model="profileData.current_password"
                type="password"
                required
              ></ion-input>
            </ion-item>

            <ion-item v-if="isEditing">
              <ion-label position="stacked">{{ t('profile.fields.newPassword') }}</ion-label>
              <ion-input
                v-model="profileData.password"
                type="password"
                :placeholder="t('profile.fields.passwordPlaceholder')"
              ></ion-input>
            </ion-item>

            <ion-item v-if="isEditing">
              <ion-label position="stacked">{{ t('profile.fields.confirmPassword') }}</ion-label>
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
              {{ t('profile.actions.edit') }}
            </ion-button>

            <ion-button
              v-if="isEditing"
              expand="block"
              color="primary"
              @click="validateForm() && saveProfile()"
              :disabled="isSaving"
            >
              <ion-spinner v-if="isSaving" name="crescent"></ion-spinner>
              <span v-else>{{ t('profile.actions.save') }}</span>
            </ion-button>

            <ion-button
              v-if="isEditing"
              expand="block"
              fill="clear"
              @click="cancelEditing"
            >
              {{ t('profile.actions.cancel') }}
            </ion-button>
          </div>
        </ion-card-content>
      </ion-card>

      <!-- Statistics Card -->
      <ion-card class="mt-4">
        <ion-card-header>
          <ion-card-title>{{ t('profile.stats.title') }}</ion-card-title>
        </ion-card-header>

        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-label>
                <h2>{{ t('profile.stats.activeReservations') }}</h2>
                <p>{{ stats.activeReservations || 0 }}</p>
              </ion-label>
            </ion-item>
            <ion-item>
              <ion-label>
                <h2>{{ t('profile.stats.totalBooks') }}</h2>
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
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
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

const { t } = useI18n();
const authStore = useAuthStore();
const { showToast } = useToast();

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
        await showToast('toast.profile.updateError', { color: 'danger' });
        return;
      }
      
      await authStore.updatePassword({
        current_password: profileData.value.current_password,
        password: profileData.value.password,
        password_confirmation: profileData.value.password_confirmation
      });
    }

    isEditing.value = false;
    await showToast('toast.profile.updateSuccess', { color: 'success' });
  } catch (error: any) {
    console.error('Profile update error:', error);
    await showToast('toast.profile.updateError', { color: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

// Add validation before save
const validateForm = () => {
  if (!profileData.value.name.trim()) {
    showToast('toast.profile.invalidInput', { color: 'danger' });
    return false;
  }
  
  if (profileData.value.password && profileData.value.password.length < 8) {
    showToast('toast.profile.invalidInput', { color: 'danger' });
    return false;
  }
  
  if (profileData.value.password !== profileData.value.password_confirmation) {
    showToast('toast.profile.invalidInput', { color: 'danger' });
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