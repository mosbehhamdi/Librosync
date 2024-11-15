import { toastController } from '@ionic/vue';
import { useI18n } from 'vue-i18n';
import { useRtl } from '@/composables/useRtl';

export interface ToastOptions {
  color?: 'success' | 'danger' | 'warning' | 'primary';
  duration?: number;
  position?: 'top' | 'bottom' | 'middle';
}

export function useToast() {
  const { t } = useI18n();
  const { isRtl } = useRtl();

  const showToast = async (messageKey: string, options?: ToastOptions) => {
    const toast = await toastController.create({
      message: t(messageKey),
      duration: options?.duration || 3000,
      color: options?.color || 'primary',
      position: options?.position || 'bottom',
      cssClass: isRtl.value ? 'toast-rtl' : '',
      buttons: [
        {
          text: t('common.actions.close'),
          role: 'cancel'
        }
      ]
    });
    await toast.present();
  };

  return { showToast };
} 