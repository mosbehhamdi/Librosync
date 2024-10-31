<template>
  <ion-modal :is-open="isOpen" @didDismiss="closeModal">
    <ion-header>
      <ion-toolbar>
        <ion-title>{{ book ? 'Edit Book' : 'Add New Book' }}</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="closeModal">Close</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <form @submit.prevent="handleSubmit">
        <ion-list>
          <!-- Basic Information -->
          <ion-item>
            <ion-input
              v-model="formData.title"
              label="Title"
              label-placement="floating"
              required
            ></ion-input>
          </ion-item>

          <!-- Authors (with add/remove functionality) -->
          <ion-item>
            <ion-label position="stacked">Authors</ion-label>
            <div class="space-y-2">
              <div v-for="(author, index) in formData.authors" :key="index" class="flex gap-2">
                <ion-input
                  v-model="formData.authors[index]"
                  placeholder="Author name"
                  required
                ></ion-input>
                <ion-button 
                  fill="clear" 
                  color="danger" 
                  @click="removeAuthor(index)"
                  v-if="formData.authors.length > 1"
                >
                  <ion-icon :icon="removeCircleOutline" slot="icon-only"></ion-icon>
                </ion-button>
              </div>
              <ion-button size="small" @click="addAuthor">
                <ion-icon :icon="addOutline" slot="start"></ion-icon>
                Add Author
              </ion-button>
            </div>
          </ion-item>

          <!-- Copies and Parts -->
          <ion-item>
            <ion-input
              v-model="formData.copies_count"
              type="number"
              label="Number of Copies"
              label-placement="floating"
              min="1"
              required
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-input
              v-model="formData.parts_count"
              type="number"
              label="Number of Parts"
              label-placement="floating"
              min="1"
              required
            ></ion-input>
          </ion-item>

          <!-- Publication Details -->
          <ion-item>
            <ion-input
              v-model="formData.publisher"
              label="Publisher"
              label-placement="floating"
              required
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-input
              v-model="formData.edition_number"
              type="number"
              label="Edition Number"
              label-placement="floating"
              min="1"
              required
            ></ion-input>
          </ion-item>

          <!-- Dewey Classification -->
          <ion-item>
            <ion-select
              v-model="formData.dewey_category"
              label="Dewey Category"
              label-placement="floating"
              required
            >
              <ion-select-option 
                v-for="category in deweyCategories" 
                :key="category.code"
                :value="category.code"
              >
                {{ category.code }} - {{ category.name }}
              </ion-select-option>
            </ion-select>
          </ion-item>

          <ion-item>
            <ion-input
              v-model="formData.dewey_subcategory"
              label="Dewey Subcategory"
              label-placement="floating"
            ></ion-input>
          </ion-item>

          <!-- Additional Details -->
          <ion-item>
            <ion-input
              v-model="formData.price"
              type="number"
              label="Price"
              label-placement="floating"
              min="0"
              step="0.01"
              required
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-textarea
              v-model="formData.comments"
              label="Comments"
              label-placement="floating"
              rows="3"
            ></ion-textarea>
          </ion-item>

          <!-- Reference Numbers -->
          <ion-item>
            <ion-input
              v-model="formData.central_number"
              label="Central Number"
              label-placement="floating"
              required
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-input
              v-model="formData.local_number"
              label="Local Number"
              label-placement="floating"
              required
            ></ion-input>
          </ion-item>

          <!-- Dates -->
          <ion-item>
            <ion-input
              v-model="formData.publication_date"
              type="date"
              label="Publication Date"
              label-placement="floating"
              required
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-input
              v-model="formData.acquisition_date"
              type="date"
              label="Acquisition Date"
              label-placement="floating"
              required
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-input
              v-model="formData.publication_year"
              type="number"
              label="Publication Year"
              label-placement="floating"
              :min="1000"
              :max="new Date().getFullYear()"
              required
            ></ion-input>
          </ion-item>
        </ion-list>

        <div class="p-4">
          <ion-button type="submit" expand="block">
            {{ book ? 'Update Book' : 'Add Book' }}
          </ion-button>
        </div>
      </form>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { 
  IonModal, IonHeader, IonToolbar, IonTitle, IonContent,
  IonList, IonItem, IonInput, IonTextarea, IonSelect,
  IonSelectOption, IonButton, IonButtons, IonIcon, IonLabel
} from '@ionic/vue';
import { addOutline, removeCircleOutline } from 'ionicons/icons';
import { deweyCategories } from '@/constants/dewey';

const props = defineProps({
  isOpen: Boolean,
  book: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:isOpen', 'saved']);

const resetForm = () => {
  formData.value = {
    title: '',
    authors: [''],
    copies_count: 1,
    available_copies: 1,
    parts_count: 1,
    publisher: '',
    edition_number: 1,
    dewey_category: '',
    dewey_subcategory: '',
    price: 0,
    comments: '',
    central_number: '',
    local_number: '',
    publication_date: '',
    publication_year: new Date().getFullYear(),
    acquisition_date: new Date().toISOString().split('T')[0]
  };
};

const formData = ref({
  title: '',
  authors: [''],
  copies_count: 1,
  available_copies: 1,
  parts_count: 1,
  publisher: '',
  edition_number: 1,
  dewey_category: '',
  dewey_subcategory: '',
  price: 0,
  comments: '',
  central_number: '',
  local_number: '',
  publication_date: '',
  publication_year: new Date().getFullYear(),
  acquisition_date: new Date().toISOString().split('T')[0]
});

// Watch for book prop changes to update form
watch(() => props.book, (newBook) => {
  if (newBook) {
    const publicationDate = newBook.publication_date ? new Date(newBook.publication_date).toISOString().split('T')[0] : '';
    const acquisitionDate = newBook.acquisition_date ? new Date(newBook.acquisition_date).toISOString().split('T')[0] : '';
    const publicationYear = newBook.publication_year || new Date().getFullYear();

    formData.value = {
      ...newBook,
      authors: Array.isArray(newBook.authors) ? newBook.authors : [newBook.authors || ''],
      copies_count: Number(newBook.copies_count) || 1,
      available_copies: Number(newBook.available_copies) || 1,
      parts_count: Number(newBook.parts_count) || 1,
      edition_number: Number(newBook.edition_number) || 1,
      price: Number(newBook.price) || 0,
      publication_date: publicationDate,
      publication_year: publicationYear,
      acquisition_date: acquisitionDate,
      comments: newBook.comments || '',
      dewey_subcategory: newBook.dewey_subcategory || '',
      central_number: newBook.central_number || '',
      local_number: newBook.local_number || ''
    };
  } else {
    resetForm();
  }
}, { immediate: true, deep: true });

const addAuthor = () => {
  formData.value.authors.push('');
};

const removeAuthor = (index: number) => {
  formData.value.authors.splice(index, 1);
};

const closeModal = () => {
  emit('update:isOpen', false);
  resetForm();
};

const handleSubmit = async () => {
  try {
    emit('saved', formData.value);
    closeModal();
  } catch (error) {
    console.error('Error saving book:', error);
  }
};
</script> 