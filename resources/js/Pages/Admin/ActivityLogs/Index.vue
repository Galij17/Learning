<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Activity } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import type { PaginatedData } from '@/types';

interface LogRow {
    id: number;
    action: string;
    description: string;
    user_name: string;
    user_email: string;
    ip_address: string | null;
    created_at: string;
}

const props = defineProps<{
    logs: PaginatedData<LogRow>;
    filters: { search?: string; action?: string };
}>();

const search = ref(props.filters.search ?? '');
const actionFilter = ref(props.filters.action ?? '');

function applyFilters(): void {
    router.get(route('admin.activity-logs.index'), { search: search.value, action: actionFilter.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Monitoring Aktivitas Sistem" />

    <AuthenticatedLayout title="Monitoring Aktivitas Sistem">
        <div class="space-y-5">
            <form @submit.prevent="applyFilters" class="flex flex-wrap gap-2">
                <input v-model="search" type="search" placeholder="Cari deskripsi atau aksi..." class="min-h-[44px] min-w-[240px] flex-1 rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 text-sm dark:border-[#1B2CC1] dark:bg-[#283548] sm:max-w-sm" />
                <select v-model="actionFilter" @change="applyFilters" aria-label="Filter kategori aksi" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]">
                    <option value="">Semua Kategori</option>
                    <option value="auth.">Otentikasi</option>
                    <option value="admin.">Administrasi</option>
                    <option value="instructor.">Pengajar</option>
                    <option value="student.">Siswa</option>
                    <option value="quiz.">Ujian & Kuis</option>
                </select>
                <button type="submit" class="min-h-[44px] rounded-[8px] border border-[#1B2CC1] px-4 text-sm font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">Cari</button>
            </form>

            <EmptyState v-if="logs.data.length === 0" :icon="Activity" title="Tidak ada log aktivitas" description="Belum ada catatan aktivitas yang cocok dengan filter." />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Waktu</th>
                                <th class="px-4 py-3 font-semibold">Pengguna</th>
                                <th class="px-4 py-3 font-semibold">Aksi</th>
                                <th class="px-4 py-3 font-semibold">Keterangan</th>
                                <th class="px-4 py-3 font-semibold">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="log in logs.data" :key="log.id">
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ log.created_at }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ log.user_name }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ log.user_email }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-[#1B2CC1] dark:text-[#ABD2FA]">{{ log.action }}</td>
                                <td class="px-4 py-3">{{ log.description }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ log.ip_address || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationBar :links="logs.links" :from="logs.from" :to="logs.to" :total="logs.total" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
