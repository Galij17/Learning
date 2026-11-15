<script setup lang="ts">
interface Props {
    lines?: number;
    label?: string;
}

withDefaults(defineProps<Props>(), {
    lines: 3,
    label: 'Memuat konten...',
});
</script>

<template>
    <div
        role="status"
        :aria-label="label"
        class="space-y-3 rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
    >
        <p class="sr-only">{{ label }}</p>
        <div
            v-for="idx in lines"
            :key="idx"
            class="h-4 animate-pulse rounded bg-[#E2E8F0] dark:bg-[#3E4F69]"
            :class="idx === 1 ? 'w-1/2' : idx === lines ? 'w-2/3' : 'w-full'"
        />
    </div>
</template>
