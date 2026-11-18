<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, RefreshCw } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import type { Course } from '@/types';

interface GradebookRow {
    student_id: number;
    student_name: string;
    identity_number: string | null;
    email: string;
    progress_percentage: number;
    assignment_avg: number;
    quiz_avg: number;
    exam_avg: number;
    final_score: number;
    letter_grade: string;
    instructor_feedback: string;
}

const props = defineProps<{
    course: Course;
    rows: GradebookRow[];
}>();

const editingRow = ref<GradebookRow | null>(null);
const feedbackForm = useForm({
    instructor_feedback: '',
});

function openFeedback(row: GradebookRow): void {
    editingRow.value = row;
    feedbackForm.clearErrors();
    feedbackForm.instructor_feedback = row.instructor_feedback ?? '';
}

function submitFeedback(): void {
    if (!editingRow.value) return;
    feedbackForm.post(route('instructor.gradebook.update-feedback', [props.course.id, editingRow.value.student_id]), {
        onSuccess: () => (editingRow.value = null),
    });
}

function recalculateAll(): void {
    router.post(route('instructor.gradebook.recalculate', props.course.id));
}
</script>

<template>
    <Head :title="`Rekap Nilai - ${course.title}`" />

    <AuthenticatedLayout :title="`Rekap Nilai: ${course.code}`">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="route('instructor.courses.show', course.id)"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] border border-[#E2E8F0] bg-white px-4 text-sm font-semibold dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Kembali ke Kelas</span>
                </Link>

                <button
                    type="button"
                    @click="recalculateAll"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    <RefreshCw class="h-4 w-4" aria-hidden="true" />
                    <span>Hitung Ulang Nilai & Terbitkan Sertifikat</span>
                </button>
            </div>

            <div class="rounded-[10px] bg-[#E2E8F0] px-4 py-2.5 text-xs font-semibold text-[#1E293B] dark:bg-[#3E4F69] dark:text-[#ABD2FA]">
                Bobot aktif: Tugas {{ course.assignment_weight }}% • Kuis {{ course.quiz_weight }}% • Ujian {{ course.exam_weight }}% | Syarat sertifikat: Progres >= {{ course.min_completion_percentage }}% & Nilai >= {{ course.min_passing_score }}
            </div>

            <EmptyState v-if="rows.length === 0" title="Belum ada peserta di kelas ini" description="Tambahkan peserta atau bagikan kode kelas agar siswa dapat bergabung." />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Peserta</th>
                                <th class="px-4 py-3 font-semibold">Progres</th>
                                <th class="px-4 py-3 font-semibold">Tugas</th>
                                <th class="px-4 py-3 font-semibold">Kuis</th>
                                <th class="px-4 py-3 font-semibold">Ujian</th>
                                <th class="px-4 py-3 font-semibold">Nilai Akhir</th>
                                <th class="px-4 py-3 font-semibold">Mutu</th>
                                <th class="px-4 py-3 text-right font-semibold">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="row in rows" :key="row.student_id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ row.student_name }}</p>
                                    <p class="font-mono text-xs text-[#475569] dark:text-[#CBD5E1]">{{ row.identity_number || row.email }}</p>
                                </td>
                                <td class="px-4 py-3">{{ row.progress_percentage.toFixed(1) }}%</td>
                                <td class="px-4 py-3">{{ row.assignment_avg.toFixed(1) }}</td>
                                <td class="px-4 py-3">{{ row.quiz_avg.toFixed(1) }}</td>
                                <td class="px-4 py-3">{{ row.exam_avg.toFixed(1) }}</td>
                                <td class="px-4 py-3 font-bold">{{ row.final_score.toFixed(1) }}</td>
                                <td class="px-4 py-3 font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">{{ row.letter_grade }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" @click="openFeedback(row)" class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs font-semibold dark:border-[#1B2CC1]">
                                        {{ row.instructor_feedback ? 'Edit Catatan' : 'Beri Catatan' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <AppModal :show="Boolean(editingRow)" :title="`Catatan Pengajar: ${editingRow?.student_name ?? ''}`" @close="editingRow = null">
            <form @submit.prevent="submitFeedback" class="space-y-4">
                <div>
                    <label for="gb-fb" class="block text-sm font-semibold">Feedback Akhir Kelas</label>
                    <textarea id="gb-fb" v-model="feedbackForm.instructor_feedback" rows="4" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <InputError :message="feedbackForm.errors.instructor_feedback" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="editingRow = null" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="feedbackForm.processing" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Simpan</button>
                </div>
            </form>
        </AppModal>
    </AuthenticatedLayout>
</template>
