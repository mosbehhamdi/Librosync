<template>
  <ion-modal :is-open="isOpen" @didDismiss="closeModal">
    <ion-header>
      <ion-toolbar>
        <ion-title>{{ book ? 'Edit Book' : 'Add New Book' }}</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="closeModal">Close</ion-button>
          <ion-button @click="fillWrongValues">Fill Wrong</ion-button>
          <ion-button @click="fillRightValues">Fill Right</ion-button>
        </ion-buttons>  
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding modal-content">
      <form @submit.prevent="handleSubmit">
        <ion-list>
          <!-- Basic Information -->
          <ion-item>
            <ion-input
              v-model="formData.title"
              label="Title"
              label-placement="floating"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.title" class="error-text">{{ errors.title }}</ion-text>

          <!-- Authors (with add/remove functionality) -->
          <ion-item>
            <ion-label position="stacked">Authors</ion-label>
            <div class="space-y-2">
              <div v-for="(author, index) in formData.authors" :key="index" class="flex gap-2">
                <ion-input
                  v-model="formData.authors[index]"
                  placeholder="Author name"
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
          <ion-text color="danger" v-if="errors.authors" class="error-text">{{ errors.authors }}</ion-text>

          <!-- Copies and Parts -->
          <ion-item>
            <ion-input
              v-model="formData.copies_count"
              type="number"
              label="Number of Copies"
              label-placement="floating"
              min="1"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.copies_count" class="error-text">{{ errors.copies_count }}</ion-text>

          <ion-item>
            <ion-input
              v-model="formData.available_copies"
              type="number"
              label="Available Copies"
              label-placement="floating"
              min="0"
              :max="formData.copies_count"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.available_copies" class="error-text">{{ errors.available_copies }}</ion-text>

          <ion-item>
            <ion-input
              v-model="formData.parts_count"
              type="number"
              label="Number of Parts"
              label-placement="floating"
              min="1"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.parts_count" class="error-text">{{ errors.parts_count }}</ion-text>

          <!-- Publication Details -->
          <ion-item>
            <ion-input
              v-model="formData.publisher"
              label="Publisher"
              label-placement="floating"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.publisher" class="error-text">{{ errors.publisher }}</ion-text>

          <ion-item>
            <ion-input
              v-model="formData.edition_number"
              type="number"
              label="Edition Number"
              label-placement="floating"
              min="1"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.edition_number" class="error-text">{{ errors.edition_number }}</ion-text>

          <!-- Dewey Classification -->
          <ion-item>
            <ion-select
              v-model="formData.dewey_category"
              label="Dewey Category"
              label-placement="floating"
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
          <ion-text color="danger" v-if="errors.dewey_category" class="error-text">{{ errors.dewey_category }}</ion-text>

          <ion-item>
            <ion-input
              v-model="formData.dewey_subcategory"
              label="Dewey Subcategory"
              label-placement="floating"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.dewey_subcategory" class="error-text">{{ errors.dewey_subcategory }}</ion-text>

          <!-- Additional Details -->
          <ion-item>
            <ion-input
              v-model="formData.price"
              type="number"
              label="Price"
              label-placement="floating"
              min="0"
              step="0.01"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.price" class="error-text">{{ errors.price }}</ion-text>

          <ion-item>
            <ion-textarea
              v-model="formData.comments"
              label="Comments"
              label-placement="floating"
              rows="3"
            ></ion-textarea>
          </ion-item>
          <ion-text color="danger" v-if="errors.comments" class="error-text">{{ errors.comments }}</ion-text>

          <!-- Reference Numbers -->
          <ion-item>
            <ion-input
              v-model="formData.central_number"
              label="Central Number"
              label-placement="floating"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.central_number" class="error-text">{{ errors.central_number }}</ion-text>

          <ion-item>
            <ion-input
              v-model="formData.local_number"
              label="Local Number"
              label-placement="floating"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.local_number" class="error-text">{{ errors.local_number }}</ion-text>

          <!-- ISBN -->
          <ion-item>
            <ion-input
              v-model="formData.isbn"
              label="ISBN"
              label-placement="floating"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.isbn" class="error-text">{{ errors.isbn }}</ion-text>

          <!-- Dates -->
          <ion-item>
            <ion-input
              v-model="formData.publication_date"
              type="date"
              label="Publication Date"
              label-placement="floating"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.publication_date" class="error-text">{{ errors.publication_date }}</ion-text>

          <ion-item>
            <ion-input
              v-model="formData.acquisition_date"
              type="date"
              label="Acquisition Date"
              label-placement="floating"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.acquisition_date" class="error-text">{{ errors.acquisition_date }}</ion-text>

          <ion-item>
            <ion-input
              v-model="formData.publication_year"
              type="number"
              label="Publication Year"
              label-placement="floating"
              :min="1000"
              :max="new Date().getFullYear()"
            ></ion-input>
          </ion-item>
          <ion-text color="danger" v-if="errors.publication_year" class="error-text">{{ errors.publication_year }}</ion-text>
        </ion-list>
        <ion-footer class="fixed-footer">
          <ion-button type="submit" expand="block">
            {{ book ? 'Update Book' : 'Add Book' }}
          </ion-button>
        </ion-footer>
      </form>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import { 
  IonModal, IonHeader, IonToolbar, IonTitle, IonContent,
  IonList, IonItem, IonInput, IonTextarea, IonSelect,
  IonSelectOption, IonButton, IonButtons, IonIcon, IonLabel,
  IonText, toastController
} from '@ionic/vue';
import { addOutline, removeCircleOutline } from 'ionicons/icons';
import { deweyCategories } from '@/constants/dewey';
import { useBookStore } from '@/stores/book';
import { ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  isOpen: Boolean,
  book: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:isOpen', 'saved']);

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
  isbn: '',
  publication_date: '',
  publication_year: new Date().getFullYear(),
  acquisition_date: new Date().toISOString().split('T')[0]
});

const errors = ref({});

const bookStore = useBookStore();

const { showToast } = useToast();

const handleSubmit = async () => {
  errors.value = {}; // Reset errors
  try {
    if (props.book) {
      await bookStore.adminBookAction('update', formData.value, props.book.id);
    } else {
      await bookStore.adminBookAction('create', formData.value);
    }
    emit('saved', formData.value);
    closeModal();
    await showToast('toast.book.createSuccess', { color: 'success' });
  } catch (error) {
    if (error.response && error.response.data.errors) {
      const backendErrors = error.response.data.errors;
      for (const [field, messages] of Object.entries(backendErrors)) {
        const fieldName = field.split('.')[0];
        errors.value[fieldName] = messages.join(' ');
      }
      await showToast('toast.book.error', { color: 'danger' });
    } else if (error.response && error.response.data.error) {
      // Handle SQL error message for unique fields
      const errorMessage = error.response.data.error;
      const uniqueFields = ['isbn', 'central_number', 'local_number'];
      
      uniqueFields.forEach(field => {
        if (errorMessage.includes(`Duplicate entry`) && errorMessage.includes(`for key 'books.books_${field}_unique'`)) {
          errors.value[field] = `This ${field.replace('_', ' ')} already exists.`;
        }
      });

      await showToast('Duplicate entry detected. Please check your input.', 'danger');
    }
  }
};

const closeModal = () => {
  emit('update:isOpen', false);
  resetForm();
};

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
    isbn: '',
    publication_date: '',
    publication_year: new Date().getFullYear(),
    acquisition_date: new Date().toISOString().split('T')[0]
  };
};

const addAuthor = () => {
  formData.value.authors.push('');
};

const removeAuthor = (index) => {
  formData.value.authors.splice(index, 1);
};

const fillWrongValues = () => {
  formData.value = {
    title: 'title', // Empty title
    authors: ['author'], // Empty author
    copies_count: 1, // Invalid copies count
    available_copies: 1, // More than copies_count
    parts_count: 1, // Invalid parts count
    publisher: 'A very long publisher name that exceeds the maximum length allowed by the validation rules',
    edition_number: 1, // Invalid edition number
    dewey_category: '', // Empty category
    dewey_subcategory: 'A very long subcategory name that exceeds the maximum length allowed by the validation rules',
    price: 1, // Negative price
    comments: 'Some comments',
    central_number: '1234567890123456789012345678901234567890', // Exceeds max length
    local_number: '1234567890123456789012345678901234567890', // Exceeds max length
    isbn: '123', // Invalid ISBN
    publication_date: '2023-01-01', // Invalid date
    publication_year: 2000, // Invalid year
    acquisition_date: '2023-01-01' // Invalid date
  };
};

const fillRightValues = () => {
  formData.value = {
    title: 'Valid Book Title',
    authors: ['Author One', 'Author Two'],
    copies_count: 5,
    available_copies: 3,
    parts_count: 1,
    publisher: 'Valid Publisher',
    edition_number: 1,
    dewey_category: deweyCategories[0]?.code || '',
    dewey_subcategory: 'Valid Subcategory',
    price: 19.99,
    comments: 'Some valid comments',
    central_number: 'CN123456',
    local_number: 'LN123456',
    isbn: '9781234567890', // Valid ISBN
    publication_date: '2023-01-01',
    publication_year: 2023,
    acquisition_date: '2023-01-02'
  };
};

watch(() => props.book, (newBook) => {
  if (newBook) {
    formData.value = { ...newBook };
  }
});
</script>

<style scoped>
.fixed-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
}

.bottom-left-toast {
  --ion-toast-position-start: 10px;
  --ion-toast-position-bottom: 10px;
  --ion-toast-width: 250px;
  --ion-toast-position-left: 10px;
}

.error-text {
  margin-top: 4px;
  font-size: 0.875em;
}
</style>
