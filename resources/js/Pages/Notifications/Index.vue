<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { safeHttpUrl } from '@/utils/url';
import type { NotificationItem, PaginatedData } from '@/types';

defineProps<{
    items: PaginatedData<NotificationItem>;
}>();

const processingAction = ref(false);

function markRead(id: string): void {
    if (processingAction.value) return;
    processingAction.value = true;
    router.post(
        route('notifications.read', id),
        {},
        { preserveScroll: true, onFinish: () => (processingAction.value = false) }
    );
}

function markAllRead(): void {
    if (processingAction.value) return;
    processingAction.value = true;
    router.post(
        route('notifications.read-all'),
        {},
        { preserveScroll: true, onFinish: () => (processingAction.value = false) }
    );
}
</script>

<template>
    <Head title="Notifikasi" />

    <AuthenticatedLayout title="Pusat Notifikasi">
        <div class="mx-auto max-w-4xl space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">Semua Notifikasi</h2>
                    <p class="text-sm text-[#475569] dark:text-[#CBD5E1]">Pembaruan tugas, tenggat waktu, nilai, ujian, dan pengumuman.</p>
                </div>
                <button
                    v-if="items.data.length > 0"
                    type="button"
                    @click="markAllRead"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] border border-[#1B2CC1] px-4 text-sm font-semibold text-[#1B2CC1] hover:bg-[#E2E8F0] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69]"
                >
                    <CheckCheck class="h-4 w-4" aria-hidden="true" />
                    <span>Tandai Semua Dibaca</span>
                </button>
            </div>

            <EmptyState
                v-if="items.data.length === 0"
                :icon="Bell"
                title="Belum ada notifikasi"
                description="Notifikasi akan muncul di sini saat ada tugas baru, jadwal ujian, pengumuman, atau nilai yang dirilis."
            />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <ul class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                    <li
                        v-for="item in items.data"
                        :key="item.id"
                        class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                        :class="!item.read_at ? 'bg-[#F8FAFC] dark:bg-[#3E4F69]/40' : ''"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <StatusBadge v-if="!item.read_at" label="Baru" variant="mint" />
                                <p class="font-semibold text-[#1E293B] dark:text-[#F8FAFC]">{{ item.data.title }}</p>
                            </div>
                            <p class="mt-1 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ item.data.message }}</p>
                            <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                                {{ item.created_at ?? '' }} <span v-if="item.created_at_human">({{ item.created_at_human }})</span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Link
                                v-if="safeHttpUrl(item.data.url)"
                                :href="safeHttpUrl(item.data.url)!"
                                class="inline-flex min-h-[40px] items-center rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                            >
                                Buka
                            </Link>
                            <button
                                v-if="!item.read_at"
                                type="button"
                                :disabled="processingAction"
                                @click="markRead(item.id)"
                                class="inline-flex min-h-[40px] items-center rounded-[8px] border border-[#E2E8F0] px-3 text-xs font-semibold hover:bg-[#E2E8F0] disabled:opacity-60 dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]"
                            >
                                Tandai Dibaca
                            </button>
                        </div>
                    </li>
                </ul>
                <PaginationBar :links="items.links" :from="items.from" :to="items.to" :total="items.total" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
