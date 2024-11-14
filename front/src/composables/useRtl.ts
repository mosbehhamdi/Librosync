import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

export function useRtl() {
  const { locale } = useI18n();
  
  const isRtl = computed(() => locale.value === 'ar');
  
  const rtlClass = computed(() => isRtl.value ? 'rtl-mode' : '');
  
  const rtlStyle = computed(() => ({
    direction: isRtl.value ? 'rtl' : 'ltr'
  }));
  
  return {
    isRtl,
    rtlClass,
    rtlStyle
  };
} 