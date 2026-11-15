<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { GraduationCap, Plus, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Course, PaginatedData } from '@/types';

const props = defineProps<{
    courses: PaginatedData<Course>;
    instructors: Array<{ id: number; name: string; email: string }>;
    categories: Array<{ id: number; name: string }>;
    subjects: Array<{ id: number; name: string; category_id: number }>;
    periods: Array<{ id: number; name: string; is_active: boolean }>;
    filters: { search?: string; status?: string; period_id?: string | number };
}>();

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const periodFilter = ref(props.filters.period_id ?? '');

const showModal = ref(false);
const editing = ref<Course | null>(null);

const form = useForm({
    title: '',
    category_id: props.categories[0]?.id ?? '',
    subject_id: '' as number | string,
    academic_period_id: props.periods.find((p) => p.is_active)?.id ?? '',
    instructor_id: props.instructors[0]?.id ?? '',
    description: '',
    status: 'published',
});

function applyFilters(): void {
    router.get(
        route('admin.courses.index'),
        { search: search.value, status: statusFilter.value, period_id: periodFilter.value },
        { preserveState: true, replace: true }
    );
}

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.category_id = props.categories[0]?.id ?? '';
    form.academic_period_id = props.periods.find((p) => p.is_active)?.id ?? '';
    form.instructor_id = props.instructors[0]?.id ?? '';
    form.status = 'published';
    showModal.value = true;
}

function openEdit(c: Course): void {
    editing.value = c;
    form.title = c.title;
    form.category_id = c.category_id;
    form.subject_id = c.subject_id ?? '';
    form.academic_period_id = c.academic_period_id ?? '';
    form.instructor_id = c.instructor_id;
    form.description = c.description ?? '';
    form.status = c.status;
    showModal.value = true;
}

function submit(): void {
    if (editing.value) {
        form.put(route('admin.courses.update', editing.value.id), { onSuccess: () => (showModal.value = false) });
    } else {
        form.post(route('admin.courses.store'), { onSuccess: () => (showModal.value = false) });
    }
}

function remove(c: Course): void {
    if (confirm(`Hapus kelas "${c.title}"?`)) {
        router.delete(route('admin.courses.destroy', c.id));
    }
}
</script>

<template>
    <Head title="Manajemen Kelas" />

    <AuthenticatedLayout title="Manajemen Kelas & Pengajar">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form @submit.prevent="applyFilters" class="flex flex-wrap gap-2">
                    <input v-model="search" type="search" placeholder="Cari judul atau kode kelas..." class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]" />
                    <select v-model="statusFilter" @change="applyFilters" aria-label="Filter status kelas" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]">
                        <option value="">Semua Status</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                        <option value="archived">Archived</option>
                    </select>
                    <select v-model="periodFilter" @change="applyFilters" aria-label="Filter periode" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]">
                        <option value="">Semua Periode</option>
                        <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <button type="submit" class="min-h-[44px] rounded-[8px] border border-[#1B2CC1] px-4 text-sm font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">Filter</button>
                </form>

                <button type="button" @click="openCreate" class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    <span>Buat Kelas</span>
                </button>
            </div>

            <EmptyState v-if="courses.data.length === 0" :icon="GraduationCap" title="Belum ada kelas" description="Buat kelas baru dan tetapkan pengajar pengampu." />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Kode</th>
                                <th class="px-4 py-3 font-semibold">Kelas</th>
                                <th class="px-4 py-3 font-semibold">Pengajar</th>
                                <th class="px-4 py-3 font-semibold">Peserta</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="c in courses.data" :key="c.id">
                                <td class="px-4 py-3 font-mono font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">{{ c.code }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ c.title }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ c.category?.name }} • {{ c.academic_period?.name || '-' }}</p>
                                </td>
                                <td class="px-4 py-3">{{ c.instructor?.name }}</td>
                                <td class="px-4 py-3">{{ c.enrollments_count ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    <StatusBadge :label="c.status" :variant="c.status === 'published' ? 'mint' : c.status === 'draft' ? 'amber' : 'neutral'" />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('instructor.courses.show', c.id)" class="inline-flex min-h-[38px] items-center rounded-[8px] border border-[#E2E8F0] px-3 text-xs font-semibold dark:border-[#1B2CC1]">Kelola</Link>
                                        <button type="button" @click="openEdit(c)" class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs font-semibold dark:border-[#1B2CC1]">Edit</button>
                                        <button type="button" @click="remove(c)" aria-label="Hapus kelas" class="inline-flex min-h-[38px] min-w-[38px] items-center justify-center rounded-[8px] border border-red-200 text-red-600 dark:border-red-900 dark:text-red-400">
                                            <Trash2 class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationBar :links="courses.links" :from="courses.from" :to="courses.to" :total="courses.total" />
            </div>
        </div>

        <AppModal :show="showModal" :title="editing ? 'Edit Kelas' : 'Buat Kelas Baru'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="ac-title" class="block text-sm font-semibold">Judul Kelas</label>
                    <input id="ac-title" v-model="form.title" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ac-cat" class="block text-sm font-semibold">Kategori</label>
                        <select id="ac-cat" v-model="form.category_id" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="ac-subj" class="block text-sm font-semibold">Mata Pelajaran</label>
                        <select id="ac-subj" v-model="form.subject_id" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                            <option value="">Opsional</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ac-inst" class="block text-sm font-semibold">Pengajar</label>
                        <select id="ac-inst" v-model="form.instructor_id" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                            <option v-for="inst in instructors" :key="inst.id" :value="inst.id">{{ inst.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="ac-per" class="block text-sm font-semibold">Periode</label>
                        <select id="ac-per" v-model="form.academic_period_id" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="ac-stat" class="block text-sm font-semibold">Status</label>
                    <select id="ac-stat" v-model="form.status" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div>
                    <label for="ac-desc" class="block text-sm font-semibold">Deskripsi</label>
                    <textarea id="ac-desc" v-model="form.description" rows="3" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="form.processing" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Simpan</button>
                </div>
            </form>
        </AppModal>
    </AuthenticatedLayout>
</template>
