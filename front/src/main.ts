import { createApp,watch } from 'vue'
import App from './App.vue'
import router from './router';

import { IonicVue } from '@ionic/vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import en from './i18n/en.json';
import fr from './i18n/fr.json';
import ar from './i18n/ar.json';

/* Core CSS required for Ionic components to work properly */
import '@ionic/vue/css/core.css';

/* Basic CSS for apps built with Ionic */
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';

/* Optional CSS utils that can be commented out */
import '@ionic/vue/css/padding.css';
import '@ionic/vue/css/float-elements.css';
import '@ionic/vue/css/text-alignment.css';
import '@ionic/vue/css/text-transformation.css';
import '@ionic/vue/css/flex-utils.css';
import '@ionic/vue/css/display.css';

/**
 * Ionic Dark Mode
 * -----------------------------------------------------
 * For more info, please see:
 * https://ionicframework.com/docs/theming/dark-mode
 */

/* @import '@ionic/vue/css/palettes/dark.always.css'; */
/* @import '@ionic/vue/css/palettes/dark.class.css'; */
import '@ionic/vue/css/palettes/dark.system.css';

/* Theme variables and Tailwind */
import './theme/variables.css';

// Get the language preference
const userLanguage = localStorage.getItem('user-language');
const lastUserLanguage = localStorage.getItem('last-user-language');
const storedLanguage = userLanguage || lastUserLanguage || 'en';

const pinia = createPinia();
const i18n = createI18n({
  legacy: false,
  locale: storedLanguage,
  fallbackLocale: 'en',
  messages: { en, fr, ar }
});

// Set initial RTL direction
document.documentElement.dir = storedLanguage === 'ar' ? 'rtl' : 'ltr';
document.documentElement.lang = storedLanguage;

const app = createApp(App)
  .use(IonicVue)
  .use(pinia)
  .use(router)
  .use(i18n);

// Import and use auth store after pinia is installed
import { useAuthStore } from '@/stores/auth';
const authStore = useAuthStore();

// Clear auth state and set router
authStore.setRouter(router);

router.isReady().then(() => {
  authStore.initializeAuth().finally(() => {
    app.mount('#app');
  });
});

// Watch for language changes
watch(() => i18n.global.locale.value, (newLocale) => {
  document.documentElement.lang = newLocale;
  document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr';
});
