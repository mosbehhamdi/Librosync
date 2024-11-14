import { useI18n } from 'vue-i18n';

export function useLanguage() {
  const { locale } = useI18n();
  
  const setLanguage = (lang: string) => {
    localStorage.setItem('user-language', lang);
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = lang;
    locale.value = lang;
  };
  
  const getStoredLanguage = (): string => {
    return localStorage.getItem('user-language') || 'en';
  };
  
  return {
    setLanguage,
    getStoredLanguage
  };
} 