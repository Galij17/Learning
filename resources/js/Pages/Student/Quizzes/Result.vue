<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, XCircle } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Quiz } from '@/types';

defineProps<{
    course: { id: number; title: string; code: string };
    quiz: Quiz;
    attempt: {
        id: number;
        attempt_number: number;
        status: string;
        started_at: string;
        submitted_at: string | null;
        objective_score: number | string;
        essay_score: number | string;
        total_score: number | string;
        percentage: number | string;
        is_passed: boolean;
        needs_manual_grading: boolean;
    };
    showResults: boolean;
    reviewItems: Array<{
        question_text: string | null;
        type: string | null;
        points: number;
        selected_option_text: string | null;
        correct_option_text: string | null;
        essay_answer: string | null;
        is_correct: boolean | null;
        score_awarded: number;
        feedback: string | null;
        explanation: string | null;
    }>;
}>();
</script>

<template>
    <Head :title="`Hasil: ${quiz.title}`" />

    <AuthenticatedLayout :title="`Hasil ${quiz.title}`">
        <div class="mx-auto max-w-4xl space-y-6">
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
                <Link :href="route('student.quizzes.show', [course.id, quiz.id])" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                    <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                    Kembali ke Halaman Kuis
                </Link>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">{{ quiz.title }} (Percobaan #{{ attempt.attempt_number }})</h2>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ course.title }}</p>
                    </div>
                    <StatusBadge
                        :label="attempt.needs_manual_grading ? 'Menunggu Koreksi Essay' : attempt.is_passed ? 'Lulus KKM' : 'Belum Lulus'"
                        :variant="attempt.needs_manual_grading ? 'amber' : attempt.is_passed ? 'mint' : 'danger'"
                    />
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-4">
                    <div class="rounded-[10px] bg-[#F8FAFC] p-4 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Persentase Nilai</p>
                        <p class="mt-1 text-2xl font-extrabold text-[#1B2CC1] dark:text-[#ABD2FA]">{{ Number(attempt.percentage).toFixed(1) }}%</p>
                    </div>
                    <div class="rounded-[10px] bg-[#F8FAFC] p-4 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Skor Objektif</p>
                        <p class="mt-1 text-xl font-bold">{{ attempt.objective_score }}</p>
                    </div>
                    <div class="rounded-[10px] bg-[#F8FAFC] p-4 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Skor Essay</p>
                        <p class="mt-1 text-xl font-bold">{{ attempt.essay_score }}</p>
                    </div>
                    <div class="rounded-[10px] bg-[#F8FAFC] p-4 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Total Poin</p>
                        <p class="mt-1 text-xl font-bold">{{ attempt.total_score }}</p>
                    </div>
                </div>
            </section>

            <section v-if="!showResults" class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 text-center text-sm text-[#475569] dark:border-[#3E4F69] dark:bg-[#283548] dark:text-[#CBD5E1]">
                Pembahasan jawaban tidak ditampilkan sesuai pengaturan pengajar untuk evaluasi ini.
            </section>

            <section v-else class="space-y-4">
                <h3 class="text-base font-bold">Ringkasan Jawaban & Pembahasan</h3>
                <article
                    v-for="(item, idx) in reviewItems"
                    :key="idx"
                    class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">Soal #{{ idx + 1 }}</span>
                        <div class="flex items-center gap-2 text-xs font-bold">
                            <span>{{ item.score_awarded }} / {{ item.points }} poin</span>
                            <CheckCircle2 v-if="item.is_correct === true" class="h-4 w-4 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                            <XCircle v-else-if="item.is_correct === false" class="h-4 w-4 text-[#DC2626]" aria-hidden="true" />
                        </div>
                    </div>

                    <p class="mt-2 text-sm font-semibold">{{ item.question_text }}</p>

                    <div v-if="item.type !== 'essay'" class="mt-3 space-y-1 text-xs">
                        <p>
                            Jawaban Anda: <strong>{{ item.selected_option_text || '(Tidak dijawab)' }}</strong>
                        </p>
                        <p v-if="item.correct_option_text" class="text-[#1B2CC1] dark:text-[#ABD2FA]">
                            Kunci Jawaban: <strong>{{ item.correct_option_text }}</strong>
                        </p>
                    </div>
                    <div v-else class="mt-3 space-y-2 text-xs">
                        <div class="rounded-[8px] bg-[#F8FAFC] p-3 whitespace-pre-line dark:bg-[#162030]">
                            {{ item.essay_answer || '(Tidak dijawab)' }}
                        </div>
                        <p v-if="item.feedback" class="text-[#1B2CC1] dark:text-[#ABD2FA]">Feedback Pengajar: {{ item.feedback }}</p>
                    </div>

                    <p v-if="item.explanation" class="mt-3 rounded-[8px] bg-[#F8FAFC] p-3 text-xs text-[#1E293B] dark:bg-[#3E4F69] dark:text-[#F8FAFC]">
                        Pembahasan: {{ item.explanation }}
                    </p>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
