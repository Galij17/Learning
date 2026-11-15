<script setup lang="ts">
import type { Component } from 'vue';

interface Props {
    title: string;
    description: string;
    icon?: Component;
}

defineProps<Props>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-[14px] border border-dashed border-[#ABD2FA] bg-white px-6 py-10 text-center dark:border-[#1B2CC1] dark:bg-[#283548]"
    >
        <div
            v-if="icon"
            class="mb-3 flex h-12 w-12 items-center justify-center rounded-[10px] bg-[#E2E8F0] text-[#1B2CC1] dark:bg-[#3E4F69] dark:text-[#ABD2FA]"
        >
            <component :is="icon" class="h-6 w-6" aria-hidden="true" />
        </div>
        <h3 class="text-base font-bold text-[#1E293B] dark:text-[#F8FAFC]">{{ title }}</h3>
        <p class="mt-1 max-w-md text-sm text-[#475569] dark:text-[#CBD5E1]">{{ description }}</p>
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
