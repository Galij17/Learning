<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download, Upload } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/format';
import type { Assignment, Submission } from '@/types';

const props = defineProps<{
    course: { id: number; title: string; code: string };
    assignment: Assignment;
    submission: Submission | null;
    isPastDue: boolean;
}>();

const form = useForm({
    text_answer: props.submission?.text_answer ?? '',
    file: null as File | null,
});

const canSubmit = computed(() => !props.submission || props.submission.status !== 'graded');
const lockedByDeadline = computed(() => props.isPastDue && !props.assignment.allow_late_submission);

function submit(): void {
    form.post(route('student.assignments.submit', [props.course.id, props.assignment.id]), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head :title="assignment.title" />

    <AuthenticatedLayout :title="assignment.title">
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Assignment Details -->
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <Link :href="route('student.courses.show', course.id)" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                    <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                    Kembali ke {{ course.title }}
                </Link>

                <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-bold">{{ assignment.title }}</h2>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            Deadline: {{ formatDate(assignment.due_at) }} • Maksimal {{ assignment.max_score }} poin
                        </p>
                    </div>
                    <StatusBadge
                        :label="submission ? submission.status : isPastDue ? 'Terlewat' : 'Belum Dikumpulkan'"
                        :variant="submission?.status === 'graded' ? 'mint' : submission ? 'neutral' : isPastDue ? 'danger' : 'amber'"
                    />
                </div>

                <div class="mt-4 whitespace-pre-line rounded-[10px] bg-[#F8FAFC] p-4 text-sm leading-relaxed dark:bg-[#162030]">
                    {{ assignment.description }}
                </div>

                <div v-if="assignment.attachment_path" class="mt-4">
                    <a
                        :href="route('assignments.attachment', assignment.id)"
                        class="inline-flex min-h-[40px] items-center gap-2 rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                    >
                        <Download class="h-4 w-4" aria-hidden="true" />
                        Unduh Lampiran Soal ({{ assignment.attachment_name }})
                    </a>
                </div>
            </section>

            <!-- Grade & Feedback -->
            <section v-if="submission && submission.status === 'graded'" class="rounded-[14px] border border-[#1B2CC1] bg-[#F8FAFC] p-5 dark:border-[#7692FF] dark:bg-[#3E4F69]">
                <h3 class="text-base font-bold">Hasil Penilaian Pengajar</h3>
                <p class="mt-2 text-2xl font-extrabold text-[#1B2CC1] dark:text-[#ABD2FA]">
                    {{ submission.score }} / {{ assignment.max_score }}
                </p>
                <p v-if="submission.feedback" class="mt-2 text-sm">Feedback: {{ submission.feedback }}</p>
            </section>

            <!-- Submission Form -->
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <h3 class="text-base font-bold">Pengumpulan Jawaban</h3>

                <div v-if="submission" class="mt-3 rounded-[10px] border border-[#E2E8F0] bg-[#F8FAFC] p-3.5 text-xs dark:border-[#3E4F69] dark:bg-[#162030]">
                    <p>Terakhir dikumpulkan: {{ formatDate(submission.submitted_at) }}</p>
                    <a
                        v-if="submission.file_path"
                        :href="route('submissions.download', submission.id)"
                        class="mt-2 inline-flex items-center gap-1.5 font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                    >
                        <Download class="h-3.5 w-3.5" aria-hidden="true" />
                        Unduh File Terkirim ({{ submission.file_name }})
                    </a>
                </div>

                <p v-if="lockedByDeadline && !submission" class="mt-4 rounded-[8px] bg-[#FEE2E2] p-3 text-xs font-semibold text-[#DC2626]">
                    Batas waktu pengumpulan telah berakhir dan pengumpulan terlambat tidak diizinkan.
                </p>

                <form v-else-if="canSubmit && !lockedByDeadline" @submit.prevent="submit" class="mt-4 space-y-4">
                    <div v-if="assignment.submission_type !== 'file'">
                        <label for="sub_text" class="mb-1 block text-xs font-semibold">Jawaban Teks</label>
                        <textarea
                            id="sub_text"
                            v-model="form.text_answer"
                            rows="5"
                            placeholder="Tuliskan jawaban atau catatan pengumpulan Anda..."
                            class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                        ></textarea>
                        <p v-if="form.errors.text_answer" class="mt-1 text-xs text-[#DC2626]">{{ form.errors.text_answer }}</p>
                    </div>

                    <div v-if="assignment.submission_type !== 'text'">
                        <label for="sub_file" class="mb-1 block text-xs font-semibold">Unggah File Tugas (PDF, DOCX, ZIP, dll)</label>
                        <input
                            id="sub_file"
                            type="file"
                            @change="form.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                            class="w-full text-xs"
                        />
                        <p v-if="form.errors.file" class="mt-1 text-xs text-[#DC2626]">{{ form.errors.file }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-[8px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white hover:bg-[#142194] disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <Upload class="h-4 w-4" aria-hidden="true" />
                        {{ submission ? 'Perbarui Pengumpulan' : 'Kumpulkan Tugas' }}
                    </button>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
