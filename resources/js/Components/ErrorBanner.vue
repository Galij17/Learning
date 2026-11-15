<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next';

interface Props {
    title?: string;
    message: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Terjadi Kendala',
});
</script>

<template>
    <div
        role="alert"
        class="flex flex-col gap-2 rounded-[10px] border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950/50 dark:text-red-200 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex items-start gap-3">
            <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-red-700 dark:text-red-300" aria-hidden="true" />
            <div>
                <p class="font-semibold">{{ title }}</p>
                <p class="mt-0.5">{{ message }}</p>
            </div>
        </div>
        <div v-if="$slots.action" class="shrink-0">
            <slot name="action" />
        </div>
    </div>
</template>
