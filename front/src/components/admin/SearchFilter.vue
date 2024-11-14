<template>
  <ion-card class="mb-4">
    <ion-card-content>
            <ion-item>
              <ion-input
                v-model="search"
                :label="label"
                label-placement="floating"
                @ionInput="emitSearch"
              ></ion-input>
            </ion-item>
   
    </ion-card-content>
  </ion-card>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import {  IonItem,
  IonCard, IonCardContent,
  IonInput,
} from '@ionic/vue';
const props = defineProps({
  initialSearch: String,
  label: { type: String, default: 'Search...' }
});

const emit = defineEmits(['search']);

const search = ref(props.initialSearch || ''); // Default to empty string if undefined

const emitSearch = () => {
  const searchData = { search: search.value };
  emit('search', searchData);
};
</script>

<style scoped>
/* Add any specific styles for the SearchFilter component here */
</style>
