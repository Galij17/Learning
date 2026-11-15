<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { X } from 'lucide-vue-next';

interface Props {
    show: boolean;
    title: string;
    subtitle?: string;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
}

const props = withDefaults(defineProps<Props>(), {
    maxWidth: 'lg',
});

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const maxWidthClass = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
}[props.maxWidth];

function handleEscape(e: KeyboardEvent): void {
    if (e.key === 'Escape' && props.show) {
        emit('close');
    }
}

onMounted(() => window.addEventListener('keydown', handleEscape));
onUnmounted(() => window.removeEventListener('keydown', handleEscape));
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto bg-[#1E293B]/60 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            :aria-label="title"
            @click.self="emit('close')"
        >
            <div
                :class="[
                    'w-full rounded-[14px] border border-[#E2E8F0] bg-white shadow-2xl dark:border-[#3E4F69] dark:bg-[#283548]',
                    maxWidthClass,
                ]"
            >
                <div class="flex items-start justify-between gap-4 border-b border-[#E2E8F0] px-6 py-4 dark:border-[#3E4F69]">
                    <div>
                        <h3 class="text-lg font-bold text-[#1E293B] dark:text-[#F8FAFC]">{{ title }}</h3>
                        <p v-if="subtitle" class="mt-0.5 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ subtitle }}</p>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        aria-label="Tutup dialog"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] text-[#475569] hover:bg-[#F8FAFC] hover:text-[#1E293B] dark:text-[#CBD5E1] dark:hover:bg-[#3E4F69] dark:hover:text-[#F8FAFC]"
                    >
                        <X class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>
                <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
                    <slot />
                </div>
                <div
                    v-if="$slots.footer"
                    class="flex flex-wrap items-center justify-end gap-3 border-t border-[#E2E8F0] px-6 py-4 dark:border-[#3E4F69]"
                >
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
