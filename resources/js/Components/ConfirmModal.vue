<script setup lang="ts">
import AppModal from '@/Components/AppModal.vue';

withDefaults(
    defineProps<{
        show: boolean;
        title?: string;
        message: string;
        confirmText?: string;
        cancelText?: string;
        danger?: boolean;
        processing?: boolean;
    }>(),
    {
        title: 'Konfirmasi Aksi',
        confirmText: 'Ya, Lanjutkan',
        cancelText: 'Batal',
        danger: true,
        processing: false,
    }
);

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();
</script>

<template>
    <AppModal :show="show" :title="title" max-width="sm" @close="emit('cancel')">
        <p class="text-sm leading-relaxed text-[#475569] dark:text-[#CBD5E1]">
            {{ message }}
        </p>

        <template #footer>
            <button
                type="button"
                @click="emit('cancel')"
                :disabled="processing"
                class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold text-[#1E293B] hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:text-[#F8FAFC] dark:hover:bg-[#3E4F69]"
            >
                {{ cancelText }}
            </button>
            <button
                type="button"
                @click="emit('confirm')"
                :disabled="processing"
                :class="[
                    'min-h-[40px] rounded-[8px] px-4 text-xs font-semibold text-white disabled:opacity-60',
                    danger ? 'bg-red-600 hover:bg-red-700' : 'bg-[#1B2CC1] hover:bg-[#142194]',
                ]"
            >
                {{ processing ? 'Memproses...' : confirmText }}
            </button>
        </template>
    </AppModal>
</template>
