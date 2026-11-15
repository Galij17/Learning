<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Clock, HelpCircle, PlayCircle } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Quiz, QuizAttempt } from '@/types';

const props = defineProps<{
    course: { id: number; title: string; code: string };
    quiz: Quiz;
    attempts: QuizAttempt[];
    activeAttempt: QuizAttempt | null;
    canStart: boolean;
    scheduleStatus: 'open' | 'upcoming' | 'closed';
}>();

function startAttempt(): void {
    router.post(route('student.quizzes.start', [props.course.id, props.quiz.id]));
}

function formatDate(val?: string | null): string {
    if (!val) return '-';
    return new Date(val).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>
    <Head :title="quiz.title" />

    <AuthenticatedLayout :title="quiz.title">
        <div class="mx-auto max-w-4xl space-y-6">
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <Link :href="route('student.courses.show', course.id)" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                    <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                    Kembali ke {{ course.title }}
                </Link>

                <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <StatusBadge :label="quiz.type === 'exam' ? 'Ujian' : 'Kuis'" variant="pine" />
                            <StatusBadge
                                :label="scheduleStatus === 'open' ? 'Dibuka' : scheduleStatus === 'upcoming' ? 'Belum Dimulai' : 'Ditutup'"
                                :variant="scheduleStatus === 'open' ? 'mint' : 'amber'"
                            />
                        </div>
                        <h2 class="mt-2 text-xl font-bold">{{ quiz.title }}</h2>
                        <p v-if="quiz.description" class="mt-2 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ quiz.description }}</p>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <Link
                            v-if="activeAttempt"
                            :href="route('student.quizzes.take', [course.id, quiz.id, activeAttempt.id])"
                            class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            <PlayCircle class="h-4 w-4" aria-hidden="true" />
                            Lanjutkan Pengerjaan
                        </Link>
                        <button
                            v-else-if="canStart"
                            type="button"
                            @click="startAttempt"
                            class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            <PlayCircle class="h-4 w-4" aria-hidden="true" />
                            Mulai {{ quiz.type === 'exam' ? 'Ujian' : 'Kuis' }}
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-4">
                    <div class="rounded-[10px] bg-[#F8FAFC] p-3.5 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Durasi</p>
                        <p class="mt-1 flex items-center gap-1.5 text-sm font-bold">
                            <Clock class="h-4 w-4" aria-hidden="true" /> {{ quiz.duration_minutes }} Menit
                        </p>
                    </div>
                    <div class="rounded-[10px] bg-[#F8FAFC] p-3.5 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Jumlah Soal</p>
                        <p class="mt-1 flex items-center gap-1.5 text-sm font-bold">
                            <HelpCircle class="h-4 w-4" aria-hidden="true" /> {{ quiz.questions_count ?? 0 }} Soal
                        </p>
                    </div>
                    <div class="rounded-[10px] bg-[#F8FAFC] p-3.5 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Batas Percobaan</p>
                        <p class="mt-1 text-sm font-bold">{{ attempts.length }} / {{ quiz.max_attempts }}</p>
                    </div>
                    <div class="rounded-[10px] bg-[#F8FAFC] p-3.5 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">KKM Kelulusan</p>
                        <p class="mt-1 text-sm font-bold">{{ quiz.passing_score }}%</p>
                    </div>
                </div>
            </section>

            <!-- Attempt History -->
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <h3 class="text-base font-bold">Riwayat Percobaan Anda</h3>

                <p v-if="attempts.length === 0" class="mt-3 text-xs text-[#475569] dark:text-[#CBD5E1]">
                    Anda belum pernah mengerjakan {{ quiz.type === 'exam' ? 'ujian' : 'kuis' }} ini.
                </p>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="py-2.5 pr-4">Percobaan</th>
                                <th class="py-2.5 pr-4">Mulai</th>
                                <th class="py-2.5 pr-4">Status</th>
                                <th class="py-2.5 pr-4">Nilai</th>
                                <th class="py-2.5 text-right">Hasil</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="att in attempts" :key="att.id">
                                <td class="py-3 pr-4 font-semibold">#{{ att.attempt_number }}</td>
                                <td class="py-3 pr-4 text-xs">{{ formatDate(att.started_at) }}</td>
                                <td class="py-3 pr-4">
                                    <StatusBadge
                                        :label="att.needs_manual_grading ? 'Menunggu Nilai Essay' : att.status"
                                        :variant="att.status === 'in_progress' ? 'amber' : att.is_passed ? 'mint' : 'neutral'"
                                    />
                                </td>
                                <td class="py-3 pr-4 font-bold">
                                    {{ att.status === 'in_progress' ? '-' : `${Number(att.percentage).toFixed(1)}%` }}
                                </td>
                                <td class="py-3 text-right">
                                    <Link
                                        v-if="att.status === 'in_progress'"
                                        :href="route('student.quizzes.take', [course.id, quiz.id, att.id])"
                                        class="text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                                    >
                                        Lanjutkan
                                    </Link>
                                    <Link
                                        v-else
                                        :href="route('student.quizzes.result', [course.id, quiz.id, att.id])"
                                        class="text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                                    >
                                        Lihat Detail
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
