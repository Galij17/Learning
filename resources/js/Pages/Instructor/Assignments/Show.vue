<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download, FileCheck } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Assignment, PaginatedData, Submission } from '@/types';

const props = defineProps<{
    course: { id: number; title: string; code: string };
    assignment: Assignment;
    submissions: PaginatedData<Submission>;
}>();

const gradingTarget = ref<Submission | null>(null);
const gradeForm = useForm({
    score: 0 as number | string,
    feedback: '',
});

function openGrade(sub: Submission): void {
    gradingTarget.value = sub;
    gradeForm.score = sub.score ?? 85;
    gradeForm.feedback = sub.feedback ?? '';
}

function submitGrade(): void {
    if (!gradingTarget.value) return;
    gradeForm.post(route('instructor.submissions.grade', gradingTarget.value.id), {
        onSuccess: () => {
            gradingTarget.value = null;
        },
    });
}
</script>

<template>
    <Head :title="`Pengumpulan: ${assignment.title}`" />

    <AuthenticatedLayout :title="assignment.title">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="route('instructor.courses.show', course.id)"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] border border-[#E2E8F0] bg-white px-4 text-sm font-semibold dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Kembali ke {{ course.code }}</span>
                </Link>
                <div class="text-sm text-[#475569] dark:text-[#CBD5E1]">
                    Skor Maks: <strong>{{ assignment.max_score }}</strong> •
                    Deadline: <strong>{{ assignment.due_at ? new Date(assignment.due_at).toLocaleString('id-ID') : 'Tanpa batas' }}</strong>
                </div>
            </div>

            <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <p class="whitespace-pre-line text-sm">{{ assignment.description }}</p>
                <a
                    v-if="assignment.attachment_path"
                    :href="route('assignments.attachment', assignment.id)"
                    class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                >
                    <Download class="h-4 w-4" aria-hidden="true" />
                    <span>Unduh Lampiran: {{ assignment.attachment_name }}</span>
                </a>
            </div>

            <EmptyState
                v-if="submissions.data.length === 0"
                :icon="FileCheck"
                title="Belum ada pengumpulan tugas"
                description="Daftar pengumpulan siswa akan tampil di sini setelah siswa mengirim jawaban atau berkas."
            />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Peserta</th>
                                <th class="px-4 py-3 font-semibold">Jawaban & Berkas</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Nilai</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="sub in submissions.data" :key="sub.id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ sub.student?.name }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ new Date(sub.submitted_at).toLocaleString('id-ID') }}</p>
                                </td>
                                <td class="max-w-md px-4 py-3">
                                    <p v-if="sub.text_answer" class="line-clamp-2 text-xs">{{ sub.text_answer }}</p>
                                    <a
                                        v-if="sub.file_path"
                                        :href="route('submissions.download', sub.id)"
                                        class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                                    >
                                        <Download class="h-3.5 w-3.5" aria-hidden="true" />
                                        <span>{{ sub.file_name }}</span>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :label="sub.status" :variant="sub.status === 'graded' ? 'mint' : sub.status === 'late' ? 'danger' : 'amber'" />
                                </td>
                                <td class="px-4 py-3 font-bold">{{ sub.score !== null && sub.score !== undefined ? sub.score : '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        @click="openGrade(sub)"
                                        class="min-h-[38px] rounded-[8px] bg-[#1B2CC1] px-3 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                                    >
                                        {{ sub.status === 'graded' ? 'Ubah Nilai' : 'Beri Nilai' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationBar :links="submissions.links" :from="submissions.from" :to="submissions.to" :total="submissions.total" />
            </div>
        </div>

        <AppModal :show="Boolean(gradingTarget)" :title="`Nilai Tugas: ${gradingTarget?.student?.name ?? ''}`" @close="gradingTarget = null">
            <form @submit.prevent="submitGrade" class="space-y-4">
                <div v-if="gradingTarget?.text_answer" class="rounded-[8px] bg-[#F8FAFC] p-3 text-sm dark:bg-[#1E293B]">
                    <p class="mb-1 text-xs font-semibold text-[#475569] dark:text-[#CBD5E1]">Jawaban Teks Peserta:</p>
                    <p class="whitespace-pre-line">{{ gradingTarget.text_answer }}</p>
                </div>
                <div>
                    <label for="gr-score" class="block text-sm font-semibold">Nilai (0 - {{ assignment.max_score }})</label>
                    <input id="gr-score" v-model="gradeForm.score" type="number" step="0.5" min="0" :max="Number(assignment.max_score)" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <p v-if="gradeForm.errors.score" class="mt-1 text-xs text-red-600">{{ gradeForm.errors.score }}</p>
                </div>
                <div>
                    <label for="gr-fb" class="block text-sm font-semibold">Feedback Pengajar</label>
                    <textarea id="gr-fb" v-model="gradeForm.feedback" rows="3" placeholder="Berikan catatan evaluasi yang membangun..." class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="gradingTarget = null" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="gradeForm.processing" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Simpan Nilai</button>
                </div>
            </form>
        </AppModal>
    </AuthenticatedLayout>
</template>
