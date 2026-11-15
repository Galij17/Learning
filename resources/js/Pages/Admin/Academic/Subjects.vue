<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { BookOpen, Plus, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { PaginatedData, Subject } from '@/types';

const props = defineProps<{
    subjects: PaginatedData<Subject>;
    categories: Array<{ id: number; name: string }>;
    filters: { search?: string; category_id?: string | number };
}>();

const search = ref(props.filters.search ?? '');
const categoryFilter = ref(props.filters.category_id ?? '');
const showModal = ref(false);
const editing = ref<Subject | null>(null);

const form = useForm({
    category_id: props.categories[0]?.id ?? '',
    code: '',
    name: '',
    description: '',
    is_active: true,
});

function applyFilters(): void {
    router.get(route('admin.subjects.index'), { search: search.value, category_id: categoryFilter.value }, { preserveState: true, replace: true });
}

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.category_id = props.categories[0]?.id ?? '';
    form.is_active = true;
    showModal.value = true;
}

function openEdit(subj: Subject): void {
    editing.value = subj;
    form.category_id = subj.category_id;
    form.code = subj.code;
    form.name = subj.name;
    form.description = subj.description ?? '';
    form.is_active = subj.is_active;
    showModal.value = true;
}

function submit(): void {
    if (editing.value) {
        form.put(route('admin.subjects.update', editing.value.id), { onSuccess: () => (showModal.value = false) });
    } else {
        form.post(route('admin.subjects.store'), { onSuccess: () => (showModal.value = false) });
    }
}

function remove(subj: Subject): void {
    if (confirm(`Hapus mata pelajaran ${subj.code} - ${subj.name}?`)) {
        router.delete(route('admin.subjects.destroy', subj.id));
    }
}
</script>

<template>
    <Head title="Mata Pelajaran" />

    <AuthenticatedLayout title="Manajemen Mata Pelajaran">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form @submit.prevent="applyFilters" class="flex flex-wrap gap-2">
                    <input v-model="search" type="search" placeholder="Cari kode atau nama..." class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]" />
                    <select v-model="categoryFilter" @change="applyFilters" aria-label="Filter kategori" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]">
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <button type="submit" class="min-h-[44px] rounded-[8px] border border-[#1B2CC1] px-4 text-sm font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">Filter</button>
                </form>
                <button type="button" @click="openCreate" class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    <span>Tambah Mata Pelajaran</span>
                </button>
            </div>

            <EmptyState v-if="subjects.data.length === 0" :icon="BookOpen" title="Belum ada mata pelajaran" description="Tambahkan mata pelajaran atau mata kuliah untuk digunakan pada kelas dan bank soal." />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Kode</th>
                                <th class="px-4 py-3 font-semibold">Mata Pelajaran</th>
                                <th class="px-4 py-3 font-semibold">Kategori</th>
                                <th class="px-4 py-3 font-semibold">Kelas</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="subj in subjects.data" :key="subj.id">
                                <td class="px-4 py-3 font-mono font-bold">{{ subj.code }}</td>
                                <td class="px-4 py-3 font-semibold">{{ subj.name }}</td>
                                <td class="px-4 py-3 text-[#475569] dark:text-[#CBD5E1]">{{ subj.category?.name }}</td>
                                <td class="px-4 py-3">{{ subj.courses_count ?? 0 }}</td>
                                <td class="px-4 py-3"><StatusBadge :label="subj.is_active ? 'Aktif' : 'Nonaktif'" :variant="subj.is_active ? 'mint' : 'neutral'" /></td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="openEdit(subj)" class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs font-semibold dark:border-[#1B2CC1]">Edit</button>
                                        <button type="button" @click="remove(subj)" aria-label="Hapus mata pelajaran" class="inline-flex min-h-[38px] min-w-[38px] items-center justify-center rounded-[8px] border border-red-200 text-red-600 dark:border-red-900 dark:text-red-400">
                                            <Trash2 class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationBar :links="subjects.links" :from="subjects.from" :to="subjects.to" :total="subjects.total" />
            </div>
        </div>

        <AppModal :show="showModal" :title="editing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="s-cat" class="block text-sm font-semibold">Kategori</label>
                    <select id="s-cat" v-model="form.category_id" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="s-code" class="block text-sm font-semibold">Kode</label>
                        <input id="s-code" v-model="form.code" type="text" required placeholder="Contoh: IF-301" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label for="s-name" class="block text-sm font-semibold">Nama</label>
                        <input id="s-name" v-model="form.name" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                </div>
                <div>
                    <label for="s-desc" class="block text-sm font-semibold">Deskripsi</label>
                    <textarea id="s-desc" v-model="form.description" rows="2" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                </div>
                <label class="inline-flex min-h-[44px] items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Aktif</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="form.processing" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Simpan</button>
                </div>
            </form>
        </AppModal>
    </AuthenticatedLayout>
</template>
