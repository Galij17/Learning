<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { FolderTree, Plus, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Category, PaginatedData } from '@/types';

const props = defineProps<{
    categories: PaginatedData<Category>;
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref<Category | null>(null);

const form = useForm({
    name: '',
    description: '',
    is_active: true,
});

function applySearch(): void {
    router.get(route('admin.categories.index'), { search: search.value }, { preserveState: true, replace: true });
}

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.is_active = true;
    showModal.value = true;
}

function openEdit(cat: Category): void {
    editing.value = cat;
    form.name = cat.name;
    form.description = cat.description ?? '';
    form.is_active = cat.is_active;
    showModal.value = true;
}

function submit(): void {
    if (editing.value) {
        form.put(route('admin.categories.update', editing.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route('admin.categories.store'), {
            onSuccess: () => (showModal.value = false),
        });
    }
}

function remove(cat: Category): void {
    if (confirm(`Hapus kategori "${cat.name}"?`)) {
        router.delete(route('admin.categories.destroy', cat.id));
    }
}
</script>

<template>
    <Head title="Kategori Pembelajaran" />

    <AuthenticatedLayout title="Manajemen Kategori">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form @submit.prevent="applySearch" class="flex gap-2">
                    <input v-model="search" type="search" placeholder="Cari kategori..." class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]" />
                    <button type="submit" class="min-h-[44px] rounded-[8px] border border-[#1B2CC1] px-4 text-sm font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">Cari</button>
                </form>
                <button type="button" @click="openCreate" class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    <span>Tambah Kategori</span>
                </button>
            </div>

            <EmptyState v-if="categories.data.length === 0" :icon="FolderTree" title="Belum ada kategori" description="Tambahkan kategori untuk mengelompokkan mata pelajaran dan kelas." />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nama Kategori</th>
                                <th class="px-4 py-3 font-semibold">Mapel</th>
                                <th class="px-4 py-3 font-semibold">Kelas</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="cat in categories.data" :key="cat.id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ cat.name }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ cat.description || '-' }}</p>
                                </td>
                                <td class="px-4 py-3">{{ cat.subjects_count ?? 0 }}</td>
                                <td class="px-4 py-3">{{ cat.courses_count ?? 0 }}</td>
                                <td class="px-4 py-3"><StatusBadge :label="cat.is_active ? 'Aktif' : 'Nonaktif'" :variant="cat.is_active ? 'mint' : 'neutral'" /></td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="openEdit(cat)" class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs font-semibold dark:border-[#1B2CC1]">Edit</button>
                                        <button type="button" @click="remove(cat)" aria-label="Hapus kategori" class="inline-flex min-h-[38px] min-w-[38px] items-center justify-center rounded-[8px] border border-red-200 text-red-600 dark:border-red-900 dark:text-red-400">
                                            <Trash2 class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationBar :links="categories.links" :from="categories.from" :to="categories.to" :total="categories.total" />
            </div>
        </div>

        <AppModal :show="showModal" :title="editing ? 'Edit Kategori' : 'Tambah Kategori'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="cat-name" class="block text-sm font-semibold">Nama Kategori</label>
                    <input id="cat-name" v-model="form.name" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label for="cat-desc" class="block text-sm font-semibold">Deskripsi</label>
                    <textarea id="cat-desc" v-model="form.description" rows="3" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
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
