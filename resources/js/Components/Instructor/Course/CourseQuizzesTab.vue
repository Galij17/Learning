<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { HelpCircle, Plus, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Quiz } from '@/types';

defineProps<{
    courseId: number;
    quizzes: Quiz[];
}>();

const emit = defineEmits<{
    deleteQuiz: [quizId: number];
}>();
</script>

<template>
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold">Daftar Kuis &amp; Ujian</h3>
            <div class="flex gap-2">
                <Link :href="route('instructor.question-bank.index')" class="inline-flex min-h-[40px] items-center rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
                    Buka Bank Soal
                </Link>
                <Link :href="route('instructor.quizzes.create', courseId)" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    Buat Kuis / Ujian
                </Link>
            </div>
        </div>

        <EmptyState
            v-if="quizzes.length === 0"
            :icon="HelpCircle"
            title="Belum ada kuis atau ujian"
            description="Buat kuis atau ujian baru lalu tambahkan soal dari bank soal atau buat langsung."
        />

        <div v-else class="grid gap-4 md:grid-cols-2">
            <article
                v-for="quiz in quizzes"
                :key="quiz.id"
                class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <StatusBadge :label="quiz.type === 'exam' ? 'Ujian' : 'Kuis'" :variant="quiz.type === 'exam' ? 'pine' : 'mint'" />
                            <StatusBadge :label="quiz.is_published ? 'Publik' : 'Draft'" :variant="quiz.is_published ? 'mint' : 'amber'" />
                        </div>
                        <h4 class="mt-2 text-base font-bold">{{ quiz.title }}</h4>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            {{ quiz.duration_minutes }} menit • {{ quiz.questions_count ?? 0 }} soal • {{ quiz.attempts_count ?? 0 }} percobaan • KKM {{ quiz.passing_score }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-[#E2E8F0] pt-3 dark:border-[#3E4F69]">
                    <Link :href="route('instructor.quizzes.show', [courseId, quiz.id])" class="inline-flex min-h-[38px] items-center rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        Kelola Soal &amp; Nilai Essay
                    </Link>
                    <button type="button" @click="emit('deleteQuiz', quiz.id)" aria-label="Hapus kuis" class="rounded-[8px] p-2 text-[#DC2626] hover:bg-[#FEE2E2]">
                        <Trash2 class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </article>
        </div>
    </section>
</template>
