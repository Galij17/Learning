<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import { AlertCircle, CheckCircle2, Info, TriangleAlert, X } from 'lucide-vue-next';

const { toasts, dismiss } = useToast();

const styleMap = {
    success: 'border-[#7692FF] bg-white text-[#1E293B] dark:bg-[#283548] dark:text-[#F8FAFC] dark:border-[#ABD2FA]',
    error: 'border-red-600 bg-white text-[#1E293B] dark:bg-[#283548] dark:text-[#F8FAFC] dark:border-red-400',
    warning: 'border-amber-600 bg-white text-[#1E293B] dark:bg-[#283548] dark:text-[#F8FAFC] dark:border-amber-400',
    info: 'border-[#1B2CC1] bg-white text-[#1E293B] dark:bg-[#283548] dark:text-[#F8FAFC] dark:border-[#7692FF]',
};

const iconMap = {
    success: CheckCircle2,
    error: AlertCircle,
    warning: TriangleAlert,
    info: Info,
};
</script>

<template>
    <div
        class="pointer-events-none fixed right-4 bottom-4 z-50 flex w-full max-w-sm flex-col gap-2.5 sm:right-6 sm:bottom-6"
        aria-live="polite"
    >
        <div
            v-for="toast in toasts"
            :key="toast.id"
            role="status"
            :class="[
                'pointer-events-auto flex items-start gap-3 rounded-[10px] border-l-4 px-4 py-3 shadow-lg ring-1 ring-black/5 dark:ring-white/10',
                styleMap[toast.type],
            ]"
        >
            <component
                :is="iconMap[toast.type]"
                class="mt-0.5 h-5 w-5 shrink-0"
                :class="
                    toast.type === 'error'
                        ? 'text-red-600 dark:text-red-400'
                        : toast.type === 'warning'
                          ? 'text-amber-600 dark:text-amber-400'
                          : 'text-[#1B2CC1] dark:text-[#ABD2FA]'
                "
                aria-hidden="true"
            />
            <p class="flex-1 text-sm font-medium leading-snug">{{ toast.message }}</p>
            <button
                type="button"
                @click="dismiss(toast.id)"
                aria-label="Tutup notifikasi"
                class="-mr-1 -mt-1 inline-flex h-8 w-8 items-center justify-center rounded-md text-[#475569] hover:bg-[#E2E8F0] hover:text-[#1E293B] dark:text-[#CBD5E1] dark:hover:bg-[#3E4F69] dark:hover:text-[#F8FAFC]"
            >
                <X class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>
