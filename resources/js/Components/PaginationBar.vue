<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

interface Props {
    links: PaginationLink[];
    from?: number | null;
    to?: number | null;
    total?: number;
}

defineProps<Props>();

function cleanLabel(label: string): string {
    return label
        .replace(/&laquo;\s*Previous/i, 'Sebelumnya')
        .replace(/Next\s*&raquo;/i, 'Berikutnya')
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»');
}
</script>

<template>
    <div
        v-if="links && links.length > 3"
        class="flex flex-col items-center justify-between gap-3 border-t border-[#E2E8F0] px-4 py-3 dark:border-[#3E4F69] sm:flex-row"
    >
        <p class="text-sm text-[#475569] dark:text-[#CBD5E1]">
            Menampilkan <span class="font-semibold text-[#1E293B] dark:text-[#F8FAFC]">{{ from ?? 0 }}</span> -
            <span class="font-semibold text-[#1E293B] dark:text-[#F8FAFC]">{{ to ?? 0 }}</span> dari
            <span class="font-semibold text-[#1E293B] dark:text-[#F8FAFC]">{{ total ?? 0 }}</span> data
        </p>
        <nav aria-label="Navigasi halaman" class="flex flex-wrap items-center gap-1.5">
            <template v-for="(link, idx) in links" :key="idx">
                <span
                    v-if="!link.url"
                    class="inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-[8px] px-3 text-sm text-[#475569]/60 dark:text-[#CBD5E1]/50"
                >
                    {{ cleanLabel(link.label) }}
                </span>
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-[8px] px-3 text-sm font-medium transition-colors"
                    :class="
                        link.active
                            ? 'bg-[#1B2CC1] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]'
                            : 'border border-[#E2E8F0] bg-white text-[#1E293B] hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:bg-[#283548] dark:text-[#F8FAFC] dark:hover:bg-[#3E4F69]'
                    "
                >
                    {{ cleanLabel(link.label) }}
                </Link>
            </template>
        </nav>
    </div>
</template>
