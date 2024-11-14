<template>
  <ion-content class="ion-padding">
    <h1 class="text-2xl font-bold mb-6">{{ t('settings.title') }}</h1>
    
    <ion-list>
      <!-- Language Settings -->
      <ion-item-group>
        <ion-item-divider>
          <ion-label>{{ t('settings.language.title') }}</ion-label>
        </ion-item-divider>
        
        <ion-item>
          <ion-icon :icon="languageOutline" :slot="getIconSlot(isRtl)"></ion-icon>
          <ion-select 
            v-model="currentLanguage" 
            @ionChange="changeLanguage"
            :placeholder="t('settings.language.select')"
          >
            <ion-select-option value="en">English</ion-select-option>
            <ion-select-option value="fr">Français</ion-select-option>
            <ion-select-option value="ar">العربية</ion-select-option>
          </ion-select>
        </ion-item>
      </ion-item-group>

      <!-- User Profile Section -->
      <ion-item-group>
        <ion-item-divider>
          <ion-label>{{ t('settings.profile.title') }}</ion-label>
        </ion-item-divider>
        
        <ion-item>
          <ion-label>{{ t('settings.profile.role') }}</ion-label>
          <ion-badge :color="isAdmin ? 'primary' : 'secondary'" class="role-badge">
            {{ isAdmin ? t('settings.profile.adminRole') : t('settings.profile.userRole') }}
          </ion-badge>
        </ion-item>

        <ion-item button @click="navigateToProfile">
          <ion-icon :icon="personCircleOutline" :slot="getIconSlot(isRtl)"></ion-icon>
          <ion-label>{{ t('settings.profile.edit') }}</ion-label>
        </ion-item>
      </ion-item-group>
    </ion-list>
  </ion-content>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useRtl } from '@/composables/useRtl';
import { personCircleOutline, languageOutline } from 'ionicons/icons';
import {
  IonContent,
  IonList,
  IonItem,
  IonItemDivider,
  IonLabel,
  IonSelect,
  IonSelectOption,
  IonIcon,
  IonBadge,
  IonItemGroup
} from '@ionic/vue';
import { useLanguage } from '@/composables/useLanguage';

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const { isRtl } = useRtl();
const { setLanguage } = useLanguage();

const currentLanguage = ref(localStorage.getItem('user-language') || 'en');
const isAdmin = computed(() => authStore.user?.is_admin);

const changeLanguage = async (event: any) => {
  const lang = event.detail.value;
  setLanguage(lang);
  
  // Reload after a short delay to ensure all changes are applied
  setTimeout(() => window.location.reload(), 100);
};

const navigateToProfile = () => {
  router.push('/my-profile');
};

const getIconSlot = (isRtl: boolean, defaultSlot: string = 'start') => {
  return isRtl ? (defaultSlot === 'start' ? 'end' : 'start') : defaultSlot;
};
</script>

<style scoped>
ion-item-group {
  margin-bottom: 1rem;
}

ion-item-divider {
  --background: var(--ion-color-light);
  --color: var(--ion-color-medium);
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.1em;
}

ion-select {
  width: 100%;
  max-width: 100%;
}

.role-badge {
  --padding-start: 8px;
  --padding-end: 8px;
}

:deep([dir="rtl"]) {
  .role-badge {
    margin-left: 0;
    margin-right: 8px;
  }
}

.rtl-transition {
  transition: all 0.3s ease-in-out;
}

[dir="rtl"] {
  &.ion-content {
    direction: rtl;
  }
  
  .icon-wrapper {
    transform: scaleX(-1);
  }
  
  .margin-start {
    margin-right: var(--spacing);
    margin-left: 0;
  }
}
</style> 