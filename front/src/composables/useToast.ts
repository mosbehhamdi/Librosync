import { toastController } from '@ionic/vue';
import { useI18n } from 'vue-i18n';
import { useRtl } from '@/composables/useRtl';

export function useToast() {
  const { t } = useI18n();
  const { isRtl } = useRtl();

  const showToast = async (
    message: string,
    options?: {
      color?: 'success' | 'danger' | 'warning' | 'primary',
      duration?: number,
      position?: 'top' | 'bottom' | 'middle',
      translate?: boolean,
      buttons?: Array<{ text: string, role: string }>
    }
  ) => {
    const toast = await toastController.create({
      message: options?.translate ? t(message) : message,
      duration: options?.duration || 3000,
      color: options?.color || 'primary',
      position: options?.position || 'bottom',
      cssClass: isRtl.value ? 'toast-rtl' : '',
      buttons: options?.buttons || [
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