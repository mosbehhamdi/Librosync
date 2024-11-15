import { useI18n } from 'vue-i18n';

export function useLanguage() {
  const { locale } = useI18n();
  
  const setLanguage = (lang: string) => {
    // Store both current and last language
    localStorage.setItem('user-language', lang);
    localStorage.setItem('last-user-language', lang);
    
    // Update document properties
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = lang;
    locale.value = lang;
  };
  
  const getStoredLanguage = (): string => {
    return localStorage.getItem('user-language') || 
           localStorage.getItem('last-user-language') || 
           'en';
  };
  
  return {
    setLanguage,
    getStoredLanguage
  };
} 