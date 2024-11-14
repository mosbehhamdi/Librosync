<template>
  <ion-app :class="rtlClass" :style="rtlStyle">
    <template v-if="authStore.isAuthenticated">
      <main-layout v-if="!isGuestRoute">
        <router-view></router-view>
      </main-layout>
      <router-view v-else></router-view>
    </template>
    <router-view v-else></router-view>
  </ion-app>
</template>

<script setup lang="ts">
import { IonApp } from '@ionic/vue';
import { useAuthStore } from '@/stores/auth';
import MainLayout from '@/components/common/MainLayout.vue';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useRtl } from '@/composables/useRtl';

const authStore = useAuthStore();
const route = useRoute();
const { rtlClass, rtlStyle } = useRtl();

const isGuestRoute = computed(() => {
  return route.meta.requiresGuest === true;
});
</script>
