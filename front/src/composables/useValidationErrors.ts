import { useToast } from '@/composables/useToast';

export function useValidationErrors() {
  const { showToast } = useToast();

  const handleValidationErrors = async (error: any) => {
    if (error.response?.data?.toast?.message) {
      await showToast(error.response.data.toast.message, {
        color: error.response.data.toast.color || 'danger'
      });
    } else if (error.response?.data?.errors) {
      const firstError = Object.values(error.response.data.errors)[0];
      if (Array.isArray(firstError)) {
        await showToast(firstError[0], { color: 'danger' });
      }
    } else if (error.response?.data?.message) {
      await showToast(error.response.data.message, { color: 'danger' });
    } else {
      await showToast('common.messages.error', { color: 'danger' });
    }
  };

  return {
    handleValidationErrors
  };
}
