import { storeToRefs } from 'pinia';
import { useUiStore } from '@/stores/useUiStore';

export function useToast() {
    const store = useUiStore();
    const { toasts } = storeToRefs(store);

    return {
        toasts,
        notify: store.addToast,
        success: (message: string, duration = 4500) => store.addToast(message, 'success', duration),
        error: (message: string, duration = 4500) => store.addToast(message, 'error', duration),
        warning: (message: string, duration = 4500) => store.addToast(message, 'warning', duration),
        dismiss: store.removeToast,
    };
}
