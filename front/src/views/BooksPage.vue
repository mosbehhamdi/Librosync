<template>
  <ion-content class="ion-padding">
    <!-- Filters -->
    <ion-card class="mb-4">
      <ion-card-content>
        <ion-grid>
          <ion-row>
            <ion-col size="12" size-md="6">
              <ion-input
                v-model="filters.search"
                placeholder="Search books..."
                @ionInput="debouncedSearch"
              ></ion-input>
            </ion-col>
            <ion-col size="12" size-md="6">
              <ion-select
                v-model="filters.category"
                placeholder="Select category"
                @ionChange="debouncedSearch"
              >
                <ion-select-option value="">All Categories</ion-select-option>
                <ion-select-option
                  v-for="category in deweyCategories"
                  :key="category.code"
                  :value="category.code"
                >
                  {{ category.code }} - {{ category.name }}
                </ion-select-option>
              </ion-select>
            </ion-col>
          </ion-row>
        </ion-grid>
      </ion-card-content>
    </ion-card>

    <!-- Books List -->
    <ion-list>
      <ion-item v-for="book in books" :key="book?.id" v-if="book">
        <ion-label>
          <h2>{{ book.title }}</h2>
          <p>{{ book.author }}</p>
          <p>Category: {{ getDeweyCategory(book.dewey_category) }}</p>
          <p>Available: {{ book.available_copies }} / {{ book.copies_count }}</p>
        </ion-label>
        <ReserveBookButton
          slot="end"
          :book-id="book.id"
          :available-copies="book.available_copies"
          :existing-reservation="book.user_reservation"
        />
      </ion-item>
    </ion-list>
  </ion-content>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useBookStore } from '@/stores/book';
import { deweyCategories } from '@/constants/dewey';
import ReserveBookButton from '@/components/ReserveBookButton.vue';
import { useDebounce } from '@vueuse/core';

const bookStore = useBookStore();
const books = ref([]);
const filters = ref({
  search: '',
  category: ''
});

const handleSearch = async () => {
  try {
    const response = await bookStore.fetchBooks(filters.value);
    books.value = response.data || [];
  } catch (error) {
    console.error('Error fetching books:', error);
    books.value = [];
  }
};

const debouncedSearch = useDebounce(handleSearch, 300);

onMounted(() => {
  handleSearch();
});

const getDeweyCategory = (code: string) => {
  const category = deweyCategories.find(cat => cat.code === code);
  return category ? `${category.code} - ${category.name}` : '';
};
</script> 