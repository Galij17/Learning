<script setup lang="ts">
interface Props {
    variant?: 'mint' | 'pine' | 'amber' | 'danger' | 'neutral';
    label: string;
}

withDefaults(defineProps<Props>(), {
    variant: 'neutral',
});

const variantClasses = {
    mint: 'bg-[#ABD2FA] text-[#1E293B]',
    pine: 'bg-[#1B2CC1] text-white dark:bg-[#7692FF] dark:text-[#1E293B]',
    amber: 'bg-amber-100 text-amber-900 dark:bg-amber-900/60 dark:text-amber-100',
    danger: 'bg-red-100 text-red-900 dark:bg-red-900/60 dark:text-red-100',
    neutral: 'bg-[#E2E8F0] text-[#1E293B] dark:bg-[#3E4F69] dark:text-[#F8FAFC]',
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-[6px] px-2.5 py-1 text-xs font-semibold tracking-tight"
        :class="variantClasses[variant]"
    >
        {{ label }}
    </span>
</template>
