<template>
  <ion-modal :is-open="true" @ionModalDidClose="$emit('close')">
    <ion-header>
      <ion-toolbar>
        <ion-title>Select Item</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="$emit('close')">Close</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-list>
        <ion-item
          v-for="item in items"
          :key="item.id"
          button
          @click="selectItem(item.id)"
        >
          <ion-label>{{ item.title || item.name }}</ion-label>
        </ion-item>
      </ion-list>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
import { IonModal } from '@ionic/vue'; // Import IonModal

const props = defineProps({
  items: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(); // Define emits to use emit function

const selectItem = (itemId) => {
  // Emit the selected item ID to the parent component
  emit('select', itemId);
};
</script>

<style scoped>
/* Add any specific styles for the ListFilter component here */
</style>
