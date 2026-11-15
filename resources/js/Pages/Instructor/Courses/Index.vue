<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { GraduationCap, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Course, PaginatedData } from '@/types';

const props = defineProps<{
    courses: PaginatedData<Course>;
    categories: Array<{ id: number; name: string }>;
    subjects: Array<{ id: number; name: string; category_id: number }>;
    periods: Array<{ id: number; name: string; is_active: boolean }>;
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');
const showModal = ref(false);

const form = useForm({
    title: '',
    category_id: props.categories[0]?.id ?? '',
    subject_id: '' as number | string,
    academic_period_id: props.periods.find((p) => p.is_active)?.id ?? '',
    description: '',
    status: 'published',
    assignment_weight: 30,
    quiz_weight: 30,
    exam_weight: 40,
    min_completion_percentage: 80,
    min_passing_score: 70,
    certificate_enabled: true,
});

const weightTotal = computed(() => Number(form.assignment_weight) + Number(form.quiz_weight) + Number(form.exam_weight));

function applySearch(): void {
    router.get(route('instructor.courses.index'), { search: search.value }, { preserveState: true, replace: true });
}

function submit(): void {
    form.post(route('instructor.courses.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Kelas Saya" />

    <AuthenticatedLayout title="Manajemen Kelas Pengajar">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form @submit.prevent="applySearch" class="flex gap-2">
                    <input v-model="search" type="search" placeholder="Cari judul atau kode kelas..." class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]" />
                    <button type="submit" class="min-h-[44px] rounded-[8px] border border-[#1B2CC1] px-4 text-sm font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">Cari</button>
                </form>

                <button type="button" @click="showModal = true" class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    <span>Buat Kelas Baru</span>
                </button>
            </div>

            <EmptyState v-if="courses.data.length === 0" :icon="GraduationCap" title="Belum ada kelas" description="Buat kelas pertama Anda untuk mulai mengunggah materi, tugas, dan ujian." />

            <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="course in courses.data"
                    :key="course.id"
                    class="flex flex-col justify-between rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="rounded-[6px] bg-[#E2E8F0] px-2.5 py-1 font-mono text-xs font-bold text-[#1E293B] dark:bg-[#3E4F69] dark:text-[#ABD2FA]">
                                {{ course.code }}
                            </span>
                            <StatusBadge :label="course.status" :variant="course.status === 'published' ? 'mint' : 'amber'" />
                        </div>
                        <h3 class="mt-3 text-base font-bold">{{ course.title }}</h3>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            {{ course.category?.name }} • {{ course.academic_period?.name || '-' }}
                        </p>
                        <p class="mt-2 line-clamp-2 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ course.description || 'Belum ada deskripsi kelas.' }}</p>
                    </div>

                    <div class="mt-5 border-t border-[#E2E8F0] pt-4 dark:border-[#3E4F69]">
                        <p class="mb-3 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            {{ course.enrollments_count ?? 0 }} peserta • {{ course.lessons_count ?? 0 }} bab • {{ course.quizzes_count ?? 0 }} ujian/kuis
                        </p>
                        <div class="flex gap-2">
                            <Link
                                :href="route('instructor.courses.show', course.id)"
                                class="inline-flex min-h-[40px] flex-1 items-center justify-center rounded-[8px] bg-[#1B2CC1] px-3 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                            >
                                Kelola Kelas
                            </Link>
                            <Link
                                :href="route('instructor.gradebook.index', course.id)"
                                class="inline-flex min-h-[40px] items-center justify-center rounded-[8px] border border-[#1B2CC1] px-3 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                            >
                                Nilai
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <PaginationBar v-if="courses.data.length > 0" :links="courses.links" :from="courses.from" :to="courses.to" :total="courses.total" />
        </div>

        <AppModal :show="showModal" title="Buat Kelas Baru" max-width="xl" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="ic-title" class="block text-sm font-semibold">Judul Kelas</label>
                    <input id="ic-title" v-model="form.title" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="ic-cat" class="block text-sm font-semibold">Kategori</label>
                        <select id="ic-cat" v-model="form.category_id" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="ic-subj" class="block text-sm font-semibold">Mata Pelajaran</label>
                        <select id="ic-subj" v-model="form.subject_id" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                            <option value="">Opsional</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="ic-per" class="block text-sm font-semibold">Periode</label>
                        <select id="ic-per" v-model="form.academic_period_id" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="ic-desc" class="block text-sm font-semibold">Deskripsi Kelas</label>
                    <textarea id="ic-desc" v-model="form.description" rows="3" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="ic-wt" class="block text-xs font-semibold">Bobot Tugas (%)</label>
                        <input id="ic-wt" v-model="form.assignment_weight" type="number" min="0" max="100" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                    <div>
                        <label for="ic-wq" class="block text-xs font-semibold">Bobot Kuis (%)</label>
                        <input id="ic-wq" v-model="form.quiz_weight" type="number" min="0" max="100" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                    <div>
                        <label for="ic-we" class="block text-xs font-semibold">Bobot Ujian (%)</label>
                        <input id="ic-we" v-model="form.exam_weight" type="number" min="0" max="100" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                </div>
                <p class="text-xs font-semibold" :class="weightTotal === 100 ? 'text-[#1B2CC1] dark:text-[#ABD2FA]' : 'text-red-600'">
                    Total bobot saat ini: {{ weightTotal }}% (harus 100%)
                </p>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="ic-stat" class="block text-xs font-semibold">Status</label>
                        <select id="ic-stat" v-model="form.status" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    <div>
                        <label for="ic-comp" class="block text-xs font-semibold">Syarat Progres Sertifikat (%)</label>
                        <input id="ic-comp" v-model="form.min_completion_percentage" type="number" min="0" max="100" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                    <div>
                        <label for="ic-pass" class="block text-xs font-semibold">Nilai Lulus Minimal</label>
                        <input id="ic-pass" v-model="form.min_passing_score" type="number" min="0" max="100" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="form.processing || weightTotal !== 100" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        Buat Kelas
                    </button>
                </div>
            </form>
        </AppModal>
    </AuthenticatedLayout>
</template>
