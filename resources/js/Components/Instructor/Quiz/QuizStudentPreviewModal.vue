<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Clock } from 'lucide-vue-next';
import AppModal from '@/Components/AppModal.vue';
import type { Question, Quiz } from '@/types';

const props = defineProps<{
    show: boolean;
    quiz: Quiz;
}>();

const emit = defineEmits<{
    close: [];
}>();

const previewIndex = ref(0);
const previewShowAnswers = ref(false);

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) return;
        previewIndex.value = 0;
        previewShowAnswers.value = false;
    }
);

const questions = computed(() => props.quiz.questions ?? []);
const previewQuestion = computed(() => questions.value[previewIndex.value] ?? null);

function questionPoints(q: Question): number | string {
    return (q as Question & { pivot?: { points?: number | string } }).pivot?.points ?? q.points;
}
</script>

<template>
    <AppModal :show="show" :title="`Pratinjau Tampilan Siswa: ${quiz.title}`" max-width="xl" @close="emit('close')">
        <div v-if="previewQuestion" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-[10px] bg-[#F8FAFC] px-3.5 py-2.5 text-xs dark:bg-[#162030]">
                <div class="flex items-center gap-2 font-mono font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">
                    <Clock class="h-4 w-4" aria-hidden="true" />
                    <span>{{ quiz.duration_minutes }}:00</span>
                </div>
                <label class="inline-flex items-center gap-1.5 text-xs font-medium">
                    <input v-model="previewShowAnswers" type="checkbox" class="h-3.5 w-3.5 rounded" />
                    <span>Tampilkan Kunci Jawaban</span>
                </label>
            </div>

            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="(q, idx) in questions"
                    :key="q.id"
                    type="button"
                    @click="previewIndex = idx"
                    :class="[
                        'h-8 w-8 rounded-[8px] text-xs font-bold',
                        previewIndex === idx
                            ? 'bg-[#1B2CC1] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]'
                            : 'border border-[#E2E8F0] text-[#475569] dark:border-[#3E4F69] dark:text-[#CBD5E1]',
                    ]"
                >
                    {{ idx + 1 }}
                </button>
            </div>

            <div class="rounded-[12px] border border-[#E2E8F0] p-4 dark:border-[#3E4F69]">
                <div class="flex items-center justify-between text-xs text-[#475569] dark:text-[#CBD5E1]">
                    <span>Soal {{ previewIndex + 1 }} dari {{ questions.length }}</span>
                    <span>{{ questionPoints(previewQuestion) }} poin</span>
                </div>
                <p class="mt-2 text-sm font-semibold leading-relaxed">{{ previewQuestion.question_text }}</p>

                <div v-if="previewQuestion.type === 'essay'" class="mt-3">
                    <textarea rows="4" disabled placeholder="Area jawaban essay siswa..." class="w-full rounded-[8px] border border-[#E2E8F0] bg-[#F8FAFC] p-3 text-xs dark:border-[#3E4F69] dark:bg-[#162030]"></textarea>
                </div>
                <div v-else class="mt-3 space-y-2">
                    <div
                        v-for="opt in previewQuestion.options"
                        :key="opt.id"
                        :class="[
                            'flex items-center gap-2.5 rounded-[8px] border px-3 py-2 text-xs',
                            previewShowAnswers && opt.is_correct
                                ? 'border-emerald-500 bg-emerald-50 font-semibold text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200'
                                : 'border-[#E2E8F0] dark:border-[#3E4F69]',
                        ]"
                    >
                        <span class="h-3.5 w-3.5 shrink-0 rounded-full border border-current"></span>
                        <span>{{ opt.option_text }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between">
                <button
                    type="button"
                    :disabled="previewIndex === 0"
                    @click="previewIndex--"
                    class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold disabled:opacity-40 dark:border-[#3E4F69]"
                >
                    Sebelumnya
                </button>
                <button
                    type="button"
                    :disabled="previewIndex >= questions.length - 1"
                    @click="previewIndex++"
                    class="min-h-[38px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white disabled:opacity-40 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    Berikutnya
                </button>
            </div>
        </div>
    </AppModal>
</template>
