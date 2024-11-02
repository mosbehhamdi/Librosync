<template>
  <ion-app>
    <template v-if="authStore.isAuthenticated">
      <template v-if="authStore.user?.is_admin">
        <router-view></router-view>
      </template>
      <template v-else>
        <user-layout v-if="!isGuestRoute">
          <router-view></router-view>
        </user-layout>
        <router-view v-else></router-view>
      </template>
    </template>
    <router-view v-else></router-view>
  </ion-app>
</template>

<script setup lang="ts">
import { IonApp } from '@ionic/vue';
import { useAuthStore } from '@/stores/auth';
import UserLayout from '@/components/user/UserLayout.vue';
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const authStore = useAuthStore();
const route = useRoute();

const isGuestRoute = computed(() => {
  return route.meta.requiresGuest === true;
});
</script>
