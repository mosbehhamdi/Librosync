<template>
  <admin-layout>
    <ion-content class="ion-padding">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Books Management</h1>
        <ion-button @click="openAddModal">
          <ion-icon :icon="addOutline" slot="start"></ion-icon>
          Add Book
        </ion-button>
      </div>

      <!-- Search and Filter -->
      <ion-card class="mb-4">
        <ion-card-content>
          <ion-grid>
            <ion-row>
              <ion-col size="12" size-md="4">
                <ion-item>
                  <ion-input
                    v-model="filters.search"
                    label="Search books..."
                    label-placement="floating"
                    @ionInput="debouncedSearch"
                  ></ion-input>
                </ion-item>
              </ion-col>
              <ion-col size="12" size-md="4">
                <ion-item>
                  <ion-select
                    v-model="filters.category"
                    label="Dewey Category"
                    label-placement="floating"
                    @ionChange="handleSearch"
                  >
                    <ion-select-option value="">All Categories</ion-select-option>
                    <ion-select-option 
                      v-for="cat in deweyCategories" 
                      :key="cat.code" 
                      :value="cat.code"
                    >
                      {{ cat.code }} - {{ cat.name }}
                    </ion-select-option>
                  </ion-select>
                </ion-item>
              </ion-col>
            </ion-row>
          </ion-grid>
        </ion-card-content>
      </ion-card>

      <!-- Loading State -->
      <div v-if="adminStore.isLoading" class="flex justify-center p-4">
        <ion-spinner></ion-spinner>
      </div>

      <!-- Books List -->
      <ion-list v-else>
        <ion-item v-for="book in adminStore.books" :key="book.id" class="mb-2">
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

        <!-- Empty State -->
        <ion-item v-if="adminStore.books.length === 0">
          <ion-label class="text-center py-4 text-gray-500">
            No books found
          </ion-label>
        </ion-item>
      </ion-list>

      <!-- Book Form Modal -->
      <book-form-modal
        v-model:is-open="showFormModal"
        :book="selectedBook"
        @saved="handleBookSaved"
      />
    </ion-content>
  </admin-layout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useDebounce } from '@vueuse/core';
import AdminLayout from '@/components/AdminLayout.vue';
import BookFormModal from '@/components/admin/BookFormModal.vue';
import { useAdminStore } from '@/stores/admin';
import { deweyCategories } from '@/constants/dewey';
import { 
  IonContent, IonButton, IonIcon, IonList, IonItem, IonLabel,
  IonCard, IonCardContent, IonGrid, IonRow, IonCol,
  IonInput, IonSelect, IonSelectOption, alertController,
  IonSpinner
} from '@ionic/vue';
import { 
  addOutline, createOutline, trashOutline, 
  libraryOutline 
} from 'ionicons/icons';

const adminStore = useAdminStore();
const showFormModal = ref(false);
const selectedBook = ref(null);

const filters = ref({
  search: '',
  category: ''
});

const debug = ref(null);

// Définir handleSearch avant de l'utiliser dans debouncedSearch
const handleSearch = async () => {
  try {
    const response = await adminStore.fetchBooks({
      search: filters.value.search,
      category: filters.value.category
    });
    debug.value = response; // Pour le débogage
  } catch (error) {
    console.error('Error searching books:', error);
    debug.value = { error: error.message };
  }
};

// Maintenant on peut utiliser handleSearch dans debouncedSearch
const debouncedSearch = useDebounce(handleSearch, 300);

const getDeweyCategory = (code: string) => {
  const category = deweyCategories.find(cat => cat.code === code);
  return category ? `${category.code} - ${category.name}` : code;
};

const openAddModal = () => {
  selectedBook.value = null;
  showFormModal.value = true;
};

const openEditModal = (book) => {
  selectedBook.value = book;
  showFormModal.value = true;
};

const handleBookSaved = async (bookData) => {
  try {
    if (selectedBook.value) {
      await adminStore.updateBook(selectedBook.value.id, bookData);
    } else {
      await adminStore.createBook(bookData);
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
    await adminStore.deleteBook(id);
  } catch (error) {
    console.error('Error deleting book:', error);
  }
};
</script> 