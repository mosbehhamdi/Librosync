<template>
  <ion-page>
  <ion-content class="ion-padding">
  <!-- Search Bar -->
  <ion-card class="mb-4">
  <ion-card-content>
  <ion-grid>
  <ion-row>
  <ion-col size="12" size-md="6">
  <div class="search-container">
  <ion-input
  v-model="searchQuery"
  placeholder="Search by title, author, category..."
  :debounce="300"
  clear-icon="true"
  @ionInput="updateSearchQuery"
  ></ion-input>
  <ion-button 
  @click="performSearch" 
  class="search-button"
  :disabled="bookStore.isLoading"
  >
  <ion-icon :icon="searchOutline" slot="start"></ion-icon>
  {{ bookStore.isLoading ? 'Searching...' : 'Search' }}
  </ion-button>
  </div>
  </ion-col>
  <ion-col size="12" size-md="6">
  <ion-select
  v-model="filters.category"
  placeholder="Select category"
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
  
  <!-- Initial State -->
  <ion-card v-if="!showResults && !bookStore.isLoading" class="ion-text-center">
  <ion-card-content>
  <ion-text color="medium">
  Enter search terms to find books and click "Search"
  </ion-text>
  </ion-card-content>
  </ion-card>
  
  <!-- Loading State -->
  <div v-else-if="bookStore.isLoading" class="ion-text-center">
  <ion-spinner></ion-spinner>
  </div>
  
  <!-- No Results After Search -->
  <ion-card v-else-if="showResults && books.length === 0">
  <ion-card-content class="ion-text-center">
  <ion-text color="medium">
  No books found for "{{ searchQuery }}"
  <template v-if="filters.category">
  in category {{ getDeweyCategory(filters.category) }}
  </template>
  </ion-text>
  </ion-card-content>
  </ion-card>
  
  <!-- Books List -->
  <ion-list v-if="showResults && books.length > 0">
  <ion-item v-for="book in books" :key="book.id">
  <ion-label>
  <h2 class="book-title">{{ book.title }}</h2>
  <p class="book-authors">
  <strong>Authors:</strong> {{ Array.isArray(book.authors) ? book.authors.join(', ') : book.authors }}
  </p>
  <p class="book-category">
  <strong>Category:</strong> {{ getDeweyCategory(book.dewey_category) }}
  </p>
  <p class="book-availability">
  <strong>Available:</strong> {{ book.available_copies }} / {{ book.copies_count }}
  </p>
  <p v-if="book.publisher" class="book-publisher">
  <strong>Publisher:</strong> {{ book.publisher }}
  </p>
  <p v-if="book.waiting_time > 0" class="book-waiting-time">
  <strong>Estimated waiting time:</strong> {{ book.waiting_time }} days
  </p>
  </ion-label>
  <ReserveBookButton
  slot="end"
  :book-id="book.id"
  :available-copies="book.available_copies"
  :existing-reservation="book.user_reservation"
  />
  </ion-item>
  </ion-list>
  
  <!-- Pagination -->
  <ion-infinite-scroll
  v-if="showResults"
  @ionInfinite="loadMore"
  :disabled="!hasMorePages"
  >
  <ion-infinite-scroll-content></ion-infinite-scroll-content>
  </ion-infinite-scroll>
  </ion-content>
  </ion-page>
  </template>
  
  <script setup lang="ts">
  import { ref } from 'vue';
  import { useBookStore } from '@/stores/book';
  import { deweyCategories } from '@/constants/dewey';
  import ReserveBookButton from '@/components/user/ReserveBookButton.vue';
  import { searchOutline } from 'ionicons/icons';
  
  const bookStore = useBookStore();
  const books = ref([]);
  const searchQuery = ref('');
  const currentPage = ref(1);
  const hasMorePages = ref(false);
  const error = ref(null);
  const showResults = ref(false);
  
  const filters = ref({
  category: ''
  });
  
  const performSearch = async () => { 
  showResults.value = true;
  currentPage.value = 1;
  error.value = null;
  
  try {
  const response = await bookStore.searchBooks({
  query: searchQuery.value,
  category: filters.value.category,
  page: currentPage.value
  });
  if (response?.data) {
  books.value = response.data;
  hasMorePages.value = response.current_page < response.last_page;
  } else {
  books.value = [];
  hasMorePages.value = false;
  }
  } catch (err) {
  console.error('Search error:', err);
  error.value = 'Error searching books. Please try again.';
  books.value = [];
  }
  };
  
  const updateSearchQuery = (event: any) => {
  searchQuery.value = event.detail.value;
  };
  
  const loadMore = async (event: any) => {
  if (!hasMorePages.value) {
  event.target.complete();
  return;
  }
  
  try {
  currentPage.value++;
  const response = await bookStore.searchBooks({
  query: searchQuery.value,
  category: filters.value.category,
  page: currentPage.value
  });
  if (response?.data) {
  books.value = [...books.value, ...response.data];
  hasMorePages.value = response.current_page < response.last_page;
  }
  } finally {
  event.target.complete();
  }
  };
  
  const getDeweyCategory = (code: string) => {
  const category = deweyCategories.find(cat => cat.code === code);
  return category ? `${category.code} - ${category.name}` : code;
  };
  </script>
  
  <style scoped>
  .search-container {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 1rem;
  }
  
  .search-button {
  height: 40px;
  min-width: 100px;
  }
  
  .book-title {
  font-size: 1.1em;
  font-weight: bold;
  margin-bottom: 8px;
  color: var(--ion-color-primary);
  }
  
  .book-authors, 
  .book-category, 
  .book-availability, 
  .book-publisher,
  .book-waiting-time {
  margin: 4px 0;
  font-size: 0.9em;
  }
  
  .book-waiting-time {
  color: var(--ion-color-warning);
  }
  
  ion-item {
  --padding-start: 16px;
  --padding-end: 16px;
  --padding-top: 12px;
  --padding-bottom: 12px;
  }
  
  .error-message {
  color: var(--ion-color-danger);
  text-align: center;
  margin: 1rem 0;
  }
  </style> 
  