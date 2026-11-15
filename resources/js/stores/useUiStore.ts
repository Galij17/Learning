import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface ToastItem {
    id: number;
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
}

export const useUiStore = defineStore('ui', () => {
    const isDark = ref<boolean>(document.documentElement.classList.contains('dark'));
    const sidebarOpen = ref<boolean>(false);
    const toasts = ref<ToastItem[]>([]);
    let nextId = 1;

    function toggleTheme(): void {
        isDark.value = !isDark.value;
        document.documentElement.classList.toggle('dark', isDark.value);
        try {
            localStorage.setItem('vlms-theme', isDark.value ? 'dark' : 'light');
        } catch {
            // Storage may be unavailable in restricted contexts
        }
    }

    function addToast(message: string, type: ToastItem['type'] = 'success', duration = 4500): void {
        const id = nextId++;
        toasts.value.push({ id, type, message });
        window.setTimeout(() => removeToast(id), duration);
    }

    function removeToast(id: number): void {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }

    return {
        isDark,
        sidebarOpen,
        toasts,
        toggleTheme,
        addToast,
        removeToast,
    };
});
