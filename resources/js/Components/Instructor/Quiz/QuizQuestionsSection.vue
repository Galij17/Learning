<script setup lang="ts">
import { ref, watch } from 'vue';
import { ArrowDown, ArrowUp, CheckCircle2, Edit3, HelpCircle, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Question } from '@/types';

const props = defineProps<{
    questions: Question[];
    totalQuizPoints: number;
}>();

const emit = defineEmits<{
    savePoints: [questionId: number, points: number];
    moveQuestion: [index: number, delta: -1 | 1];
    editQuestion: [question: Question];
    detachQuestion: [questionId: number];
}>();

const pointDrafts = ref<Record<number, number>>({});

watch(
    () => props.questions,
    (list) => {
        const next: Record<number, number> = {};
        for (const q of list ?? []) {
            next[q.id] = Number((q as Question & { pivot?: { points?: number | string } }).pivot?.points ?? q.points);
        }
        pointDrafts.value = next;
    },
    { immediate: true, deep: true }
);

function handleSavePoints(questionId: number): void {
    const pts = pointDrafts.value[questionId];
    if (!pts || pts < 1) return;
    emit('savePoints', questionId, pts);
}
</script>

<template>
    <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-base font-bold">Daftar Soal ({{ questions.length }} • Total {{ totalQuizPoints }} poin)</h3>
            <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">
                Soal terhubung dengan Bank Soal. Perubahan bobot poin di bawah berlaku khusus untuk kuis ini.
            </p>
        </div>

        <EmptyState
            v-if="questions.length === 0"
            :icon="HelpCircle"
            title="Belum ada soal pada kuis ini"
            description="Ambil soal dari Bank Soal, impor file Excel (.xlsx), atau buat soal baru agar siswa dapat mengerjakan."
        />

        <div v-else class="space-y-3">
            <article
                v-for="(q, index) in questions"
                :key="q.id"
                class="rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">#{{ index + 1 }}</span>
                        <StatusBadge :label="q.type" variant="mint" />
                        <StatusBadge :label="q.difficulty" :variant="q.difficulty === 'hard' ? 'amber' : 'pine'" />
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <label class="inline-flex items-center gap-1 rounded-[8px] border border-[#E2E8F0] px-2 py-1 text-xs dark:border-[#3E4F69]">
                            <span class="text-[11px] text-[#475569] dark:text-[#CBD5E1]">Poin:</span>
                            <input
                                v-model.number="pointDrafts[q.id]"
                                type="number"
                                min="1"
                                max="500"
                                aria-label="Bobot poin soal di kuis"
                                class="w-14 bg-transparent text-xs font-bold focus:outline-none"
                            />
                            <button
                                type="button"
                                @click="handleSavePoints(q.id)"
                                class="rounded bg-[#EFF6FF] px-1.5 py-0.5 text-[11px] font-semibold text-[#1B2CC1] hover:bg-[#DBEAFE] dark:bg-[#162030] dark:text-[#ABD2FA]"
                            >
                                Simpan
                            </button>
                        </label>
                        <button
                            type="button"
                            :disabled="index === 0"
                            @click="emit('moveQuestion', index, -1)"
                            aria-label="Naikkan urutan soal"
                            class="rounded-[8px] border border-[#E2E8F0] p-1.5 disabled:opacity-35 dark:border-[#3E4F69]"
                        >
                            <ArrowUp class="h-3.5 w-3.5" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            :disabled="index === questions.length - 1"
                            @click="emit('moveQuestion', index, 1)"
                            aria-label="Turunkan urutan soal"
                            class="rounded-[8px] border border-[#E2E8F0] p-1.5 disabled:opacity-35 dark:border-[#3E4F69]"
                        >
                            <ArrowDown class="h-3.5 w-3.5" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            @click="emit('editQuestion', q)"
                            aria-label="Edit isi soal"
                            class="rounded-[8px] border border-[#E2E8F0] p-1.5 hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]"
                        >
                            <Edit3 class="h-3.5 w-3.5" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            @click="emit('detachQuestion', q.id)"
                            aria-label="Lepas soal"
                            class="rounded-[8px] border border-[#FECACA] p-1.5 text-[#DC2626] hover:bg-[#FEE2E2] dark:border-[#7F1D1D]"
                        >
                            <Trash2 class="h-3.5 w-3.5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
                <p class="mt-2 text-sm font-semibold">{{ q.question_text }}</p>
                <div v-if="q.options && q.options.length > 0" class="mt-2.5 grid gap-2 sm:grid-cols-2">
                    <div
                        v-for="opt in q.options"
                        :key="opt.id"
                        :class="[
                            'flex items-center gap-2 rounded-[8px] border px-3 py-1.5 text-xs',
                            opt.is_correct
                                ? 'border-[#1B2CC1] bg-[#F8FAFC] font-semibold dark:border-[#7692FF] dark:bg-[#3E4F69]'
                                : 'border-[#E2E8F0] text-[#475569] dark:border-[#3E4F69] dark:text-[#CBD5E1]',
                        ]"
                    >
                        <CheckCircle2 v-if="opt.is_correct" class="h-3.5 w-3.5 shrink-0 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                        <span>{{ opt.option_text }}</span>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
