// composables/useToast.ts - Custom toast composable
import { toast as sonnerToast } from 'vue-sonner'

export function useToast() {
  const toast = {
    success: (message: string, options?: any) => {
      return sonnerToast.success(message, {
        position: 'top-right',
        duration: 4000,
        richColors: true,
        ...options,
        style: {
          background: '#10b981', // Green background
          color: 'white',
          border: 'none',
          ...options?.style
        }
      });
    },
    
    error: (message: string, options?: any) => {
      return sonnerToast.error(message, {
        position: 'top-right',
        duration: 5000,
        richColors: true,
        ...options,
        style: {
          background: '#ef4444', // Red background
          color: 'white',
          border: 'none',
          ...options?.style
        }
      });
    },
    
    warning: (message: string, options?: any) => {
      return sonnerToast.warning(message, {
        position: 'top-right',
        duration: 4000,
        richColors: true,
        ...options,
        style: {
          background: '#f59e0b', // Orange background
          color: 'white',
          border: 'none',
          ...options?.style
        }
      });
    },
    
    info: (message: string, options?: any) => {
      return sonnerToast.info(message, {
        position: 'top-right',
        duration: 4000,
        richColors: true,
        ...options,
        style: {
          background: '#3b82f6', // Blue background
          color: 'white',
          border: 'none',
          ...options?.style
        }
      });
    },
    
    dismiss: () => sonnerToast.dismiss()
  };
  
  return { toast };
}