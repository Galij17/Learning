<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarRange, Plus, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { AcademicPeriod, PaginatedData } from '@/types';

defineProps<{
    periods: PaginatedData<AcademicPeriod>;
}>();

const showModal = ref(false);
const editing = ref<AcademicPeriod | null>(null);

const form = useForm({
    name: '',
    code: '',
    start_date: '',
    end_date: '',
    is_active: false,
});

function openCreate(): void {
    editing.value = null;
    form.reset();
    showModal.value = true;
}

function openEdit(period: AcademicPeriod): void {
    editing.value = period;
    form.name = period.name;
    form.code = period.code;
    form.start_date = period.start_date.slice(0, 10);
    form.end_date = period.end_date.slice(0, 10);
    form.is_active = period.is_active;
    showModal.value = true;
}

function submit(): void {
    if (editing.value) {
        form.put(route('admin.periods.update', editing.value.id), { onSuccess: () => (showModal.value = false) });
    } else {
        form.post(route('admin.periods.store'), { onSuccess: () => (showModal.value = false) });
    }
}

function remove(period: AcademicPeriod): void {
    if (confirm(`Hapus periode ${period.name}?`)) {
        router.delete(route('admin.periods.destroy', period.id));
    }
}
</script>

<template>
    <Head title="Periode Akademik" />

    <AuthenticatedLayout title="Periode Akademik">
        <div class="space-y-5">
            <div class="flex justify-end">
                <button type="button" @click="openCreate" class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    <span>Tambah Periode</span>
                </button>
            </div>

            <EmptyState v-if="periods.data.length === 0" :icon="CalendarRange" title="Belum ada periode akademik" description="Buat tahun ajaran atau semester untuk mengelompokkan kelas." />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Kode</th>
                                <th class="px-4 py-3 font-semibold">Nama Periode</th>
                                <th class="px-4 py-3 font-semibold">Rentang Tanggal</th>
                                <th class="px-4 py-3 font-semibold">Kelas</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="p in periods.data" :key="p.id">
                                <td class="px-4 py-3 font-mono font-bold">{{ p.code }}</td>
                                <td class="px-4 py-3 font-semibold">{{ p.name }}</td>
                                <td class="px-4 py-3 text-xs">{{ p.start_date.slice(0, 10) }} s/d {{ p.end_date.slice(0, 10) }}</td>
                                <td class="px-4 py-3">{{ p.courses_count ?? 0 }}</td>
                                <td class="px-4 py-3"><StatusBadge :label="p.is_active ? 'Aktif' : 'Arsip'" :variant="p.is_active ? 'mint' : 'neutral'" /></td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="openEdit(p)" class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs font-semibold dark:border-[#1B2CC1]">Edit</button>
                                        <button type="button" @click="remove(p)" aria-label="Hapus periode" class="inline-flex min-h-[38px] min-w-[38px] items-center justify-center rounded-[8px] border border-red-200 text-red-600 dark:border-red-900 dark:text-red-400">
                                            <Trash2 class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationBar :links="periods.links" :from="periods.from" :to="periods.to" :total="periods.total" />
            </div>
        </div>

        <AppModal :show="showModal" :title="editing ? 'Edit Periode' : 'Tambah Periode Akademik'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="per-code" class="block text-sm font-semibold">Kode</label>
                        <input id="per-code" v-model="form.code" type="text" required placeholder="2026-1" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label for="per-name" class="block text-sm font-semibold">Nama Periode</label>
                        <input id="per-name" v-model="form.name" type="text" required placeholder="2026/2027 Ganjil" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="per-start" class="block text-sm font-semibold">Mulai</label>
                        <input id="per-start" v-model="form.start_date" type="date" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                    <div>
                        <label for="per-end" class="block text-sm font-semibold">Berakhir</label>
                        <input id="per-end" v-model="form.end_date" type="date" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
                    </div>
                </div>
                <label class="inline-flex min-h-[44px] items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Jadikan periode akademik aktif</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="form.processing" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Simpan</button>
                </div>
            </form>
        </AppModal>
    </AuthenticatedLayout>
</template>
