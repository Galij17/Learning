<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppModal from '@/Components/AppModal.vue';
import type { QuizAttempt } from '@/types';

interface EssayGradeRow {
    answer_id: number;
    question_text: string;
    max_points: number;
    essay_answer: string;
    score: number;
    feedback: string;
}

const props = defineProps<{
    attempt: QuizAttempt | null;
    courseId: number;
    quizId: number;
}>();

const emit = defineEmits<{
    close: [];
}>();

const essayRows = ref<EssayGradeRow[]>([]);
const essaySubmitting = ref(false);
const essayError = ref<string | null>(null);

watch(
    () => props.attempt,
    (att) => {
        essayError.value = null;
        if (!att) {
            essayRows.value = [];
            return;
        }
        essayRows.value = (att.answers ?? [])
            .filter((a) => a.question?.type === 'essay')
            .map((a) => ({
                answer_id: a.id,
                question_text: a.question?.question_text ?? '',
                max_points: Number(a.question?.points ?? 0),
                essay_answer: a.essay_answer ?? '(Tidak dijawab)',
                score: Number(a.score_awarded ?? 0),
                feedback: a.feedback ?? '',
            }));
    }
);

function submitEssayGrades(): void {
    if (!props.attempt || essayRows.value.length === 0) return;
    essaySubmitting.value = true;
    essayError.value = null;
    router.post(
        route('instructor.quizzes.grade-essays', [props.courseId, props.quizId, props.attempt.id]),
        {
            grades: essayRows.value.map((r) => ({
                answer_id: r.answer_id,
                score: r.score,
                feedback: r.feedback,
            })),
        },
        {
            onFinish: () => {
                essaySubmitting.value = false;
            },
            onError: (errors) => {
                essayError.value = Object.values(errors)[0] ?? 'Gagal menyimpan penilaian essay. Periksa rentang skor.';
            },
            onSuccess: () => emit('close'),
        }
    );
}
</script>

<template>
    <AppModal :show="Boolean(attempt)" :title="`Nilai Jawaban Essay (${attempt?.student?.name ?? ''})`" @close="emit('close')">
        <form @submit.prevent="submitEssayGrades" class="space-y-4">
            <div v-if="essayError" role="alert" class="rounded-[8px] border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                {{ essayError }}
            </div>
            <div v-for="row in essayRows" :key="row.answer_id" class="space-y-2 rounded-[10px] border border-[#E2E8F0] p-3.5 dark:border-[#3E4F69]">
                <p class="text-xs font-bold">{{ row.question_text }} (Maks {{ row.max_points }} poin)</p>
                <div class="whitespace-pre-line rounded-[8px] bg-[#F8FAFC] p-2.5 text-xs dark:bg-[#162030]">
                    {{ row.essay_answer }}
                </div>
                <div class="grid gap-2 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold">Skor</label>
                        <input v-model.number="row.score" type="number" min="0" :max="row.max_points" step="0.5" required class="w-full min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-2.5 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-[11px] font-semibold">Feedback</label>
                        <input v-model="row.feedback" type="text" placeholder="Catatan evaluasi..." class="w-full min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-2.5 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="emit('close')" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                <button type="submit" :disabled="essaySubmitting" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    Simpan Nilai Essay
                </button>
            </div>
        </form>
    </AppModal>
</template>
