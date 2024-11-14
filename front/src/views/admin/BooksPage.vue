<template>
  <ion-content class="ion-padding">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Books Management</h1>
      <ion-button @click="openAddModal">
        <ion-icon :icon="addOutline" slot="start"></ion-icon>
        Add Book
      </ion-button>
    </div>

    <!-- Search and Filter -->
    <ion-grid>
      <ion-row>
        <ion-col> <search-filter :initialSearch="filters.search" :initialCategory="filters.category"
            label="Search books..." @search="handleSearch" />
        </ion-col>
        <ion-col size="12" size-md="4">
          <ion-card class="mb-4">
            <ion-card-content>
            <ion-item>
                <ion-select v-model="filters.category" label="Dewey Category" label-placement="floating"
                  @ionChange="handleFilter">
                  <ion-select-option value="">All Categories</ion-select-option>
                  <ion-select-option v-for="cat in deweyCategories" :key="cat.code" :value="cat.code">
                    {{ cat.code }} - {{ cat.name }}
                  </ion-select-option>
                </ion-select>
              </ion-item>
            </ion-card-content>
          </ion-card>
        </ion-col>
      </ion-row>
    </ion-grid>

    <!-- Books List -->
    <ion-list v-if="bookStore.adminBooks.length">
      <ion-item v-for="book in bookStore.adminBooks" :key="book.id" class="mb-2">
        <ion-label>
          <h2 class="text-lg font-semibold">{{ book.title }}</h2>
          <p class="text-sm text-gray-600">
            <ion-icon :icon="peopleOutline" class="align-text-bottom"></ion-icon>
            {{ book.authors.join(', ') }}
          </p>
          <div class="flex gap-4 mt-1 text-sm text-gray-600">
            <span>
              <ion-icon :icon="copyOutline" class="align-text-bottom"></ion-icon>
              {{ book.available_copies }}/{{ book.copies_count }} copies
            </span>
            <span>
              <ion-icon :icon="libraryOutline" class="align-text-bottom"></ion-icon>
              {{ getDeweyCategory(book.dewey_category) }}
            </span>
          </div>
        </ion-label>

        <div slot="end" class="flex gap-2">
          <ion-button fill="clear" @click="openEditModal(book)">
            <ion-icon :icon="createOutline" slot="icon-only"></ion-icon>
          </ion-button>
          <ion-button fill="clear" color="danger" @click="confirmDelete(book)">
            <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
          </ion-button>
        </div>
      </ion-item>
    </ion-list>

    <!-- Empty state -->
    <ion-text v-else-if="!bookStore.isLoading" color="medium" class="text-center p-4">
      <p>{{ bookStore.error || 'No books found' }}</p>
    </ion-text>

    <!-- Pagination -->
    <ion-infinite-scroll v-if="bookStore.pagination.currentPage < bookStore.pagination.lastPage"
      @ionInfinite="loadMore" threshold="100px">
      <ion-infinite-scroll-content loading-spinner="bubbles"
        loading-text="Loading more books..."></ion-infinite-scroll-content>
    </ion-infinite-scroll>

    <book-form-modal v-model:is-open="showFormModal" :book="selectedBook" @saved="handleBookSaved" />

  </ion-content>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import BookFormModal from '@/components/admin/BookFormModal.vue';
import SearchFilter from '@/components/admin/SearchFilter.vue'; // Import the new component
import { useBookStore } from '@/stores/book';
import { deweyCategories } from '@/constants/dewey';
import {
  IonContent, IonButton, IonIcon, IonList, IonItem, IonLabel, alertController, IonText, IonInfiniteScroll, IonInfiniteScrollContent
  , IonSelect, IonSelectOption, IonGrid, IonRow, IonCol
} from '@ionic/vue';
import {
  addOutline, createOutline, trashOutline,
  libraryOutline
} from 'ionicons/icons';

const bookStore = useBookStore();
const showFormModal = ref(false);
const selectedBook = ref(null);

const filters = ref({
  search: '',
  category: ''
});

const handleFilter = async () => {
  try {
    bookStore.pagination.currentPage = 1; // Reset to first page for new searches
    await bookStore.fetchAdminBooks({
      search: filters.value.search,
      category: filters.value.category,
      page: 1
    });
  } catch (error) {
    console.error('Error searching books:', error);
  }
};


// Handle search and reset pagination
const handleSearch = async (filterData) => {
  if (!filterData) {
    //console.error('filterData is undefined');
    return; // Early return if filterData is undefined
  }

  filters.value.search = filterData.search || ''; // Default to empty string if undefined
  filters.value.category = filterData.category || ''; // Default to empty string if undefined

  try {
    bookStore.pagination.currentPage = 1; // Reset to first page for new searches
    await bookStore.fetchAdminBooks({
      search: filters.value.search,
      category: filters.value.category,
      page: 1
    });
  } catch (error) {
    console.error('Error searching books:', error);
  }
};

// Load more books
const loadMore = async (event: any) => {
  console.log('Load more triggered'); // Debugging statement

  if (bookStore.pagination.currentPage >= bookStore.pagination.lastPage) {
    console.log('No more pages to load'); // Debugging statement
    event.target.complete();
    return;
  }

  const nextPage = bookStore.pagination.currentPage + 1;
  console.log(`Loading page: ${nextPage}`); // Debugging statement
  try {
    const response = await bookStore.fetchAdminBooks({
      page: nextPage,
      search: filters.value.search,
      category: filters.value.category
    });
    console.log('Books loaded successfully', response); // Debugging statement
  } catch (error) {
    console.error('Error loading more books:', error);
  } finally {
    event.target.complete();
  }
};

// Initialize
onMounted(async () => {
  await bookStore.fetchAdminBooks({
    page: 1,
    search: filters.value.search,
    category: filters.value.category
  });
});

const debouncedSearch = useDebounceFn(handleSearch, 300);

// Watch for filter changes
watch([() => filters.value.search, () => filters.value.category], () => {
  debouncedSearch();
}, { immediate: true });

const getDeweyCategory = (code: string) => {
  const category = deweyCategories.find(cat => cat.code === code);
  return category ? `${category.code} - ${category.name}` : code;
};

const openAddModal = () => {
  selectedBook.value = null;
  showFormModal.value = true;
};

const openEditModal = (book) => {
  selectedBook.value = {
    ...book,
    authors: book.authors || [], // Ensure authors is an array
    publication_date: book.publication_date ? book.publication_date.split('T')[0] : '', // Format date for input
    acquisition_date: book.acquisition_date ? book.acquisition_date.split('T')[0] : '',
    price: book.price ? Number(book.price) : 0,
    copies_count: Number(book.copies_count),
    available_copies: Number(book.available_copies),
    parts_count: Number(book.parts_count),
    edition_number: Number(book.edition_number)
  };
  showFormModal.value = true;
};

const handleBookSaved = async (bookData) => {
  try {
    if (selectedBook.value) {
      await bookStore.adminBookAction('update', bookData, selectedBook.value.id);
    } else {
      await bookStore.adminBookAction('create', bookData);
    }
    showFormModal.value = false;
  } catch (error) {
    console.error('Error saving book:', error);
  }
};

const confirmDelete = async (book) => {
  const alert = await alertController.create({
    header: 'Confirm Delete',
    message: `Are you sure you want to delete "${book.title}"?`,
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel'
      },
      {
        text: 'Delete',
        role: 'destructive',
        handler: () => deleteBook(book.id)
      }
    ]
  });
  await alert.present();
};

const deleteBook = async (id: number) => {
  try {
    await bookStore.adminBookAction('delete', undefined, id);
  } catch (error) {
    console.error('Error deleting book:', error);
  }
};
</script>