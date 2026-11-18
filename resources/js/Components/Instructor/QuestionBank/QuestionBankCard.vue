<script setup lang="ts">
import { CheckCircle2, Copy, Edit3, Trash2 } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Question } from '@/types';

defineProps<{
    question: Question;
    selected: boolean;
}>();

const emit = defineEmits<{
    toggleSelect: [id: number];
    duplicate: [id: number];
    edit: [question: Question];
    delete: [id: number];
}>();

function typeLabel(type: string): string {
    if (type === 'multiple_choice') return 'Pilihan Ganda';
    if (type === 'true_false') return 'Benar / Salah';
    return 'Essay';
}
</script>

<template>
    <article class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <input
                    type="checkbox"
                    :checked="selected"
                    @change="emit('toggleSelect', question.id)"
                    :aria-label="`Pilih soal #${question.id}`"
                    class="h-4 w-4 rounded"
                />
                <StatusBadge :label="typeLabel(question.type)" variant="mint" />
                <StatusBadge :label="`${question.points} poin`" variant="neutral" />
                <StatusBadge :label="question.difficulty" :variant="question.difficulty === 'hard' ? 'amber' : 'pine'" />
                <span v-if="question.category || question.subject" class="text-xs text-[#475569] dark:text-[#CBD5E1]">
                    {{ question.category?.name }} <span v-if="question.subject">• {{ question.subject.name }}</span>
                </span>
            </div>
            <div class="flex gap-2">
                <button
                    type="button"
                    @click="emit('duplicate', question.id)"
                    title="Duplikat soal"
                    aria-label="Duplikat soal"
                    class="rounded-[8px] border border-[#E2E8F0] p-2 hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:hover:bg-[#3E4F69]"
                >
                    <Copy class="h-4 w-4" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    @click="emit('edit', question)"
                    aria-label="Edit soal"
                    class="rounded-[8px] border border-[#E2E8F0] p-2 hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]"
                >
                    <Edit3 class="h-4 w-4" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    @click="emit('delete', question.id)"
                    aria-label="Hapus soal"
                    class="rounded-[8px] border border-[#FECACA] p-2 text-[#DC2626] hover:bg-[#FEE2E2] dark:border-[#7F1D1D]"
                >
                    <Trash2 class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </div>

        <p class="mt-3 text-sm font-semibold leading-relaxed">{{ question.question_text }}</p>

        <div v-if="question.options && question.options.length > 0" class="mt-3 grid gap-2 sm:grid-cols-2">
            <div
                v-for="opt in question.options"
                :key="opt.id"
                :class="[
                    'flex items-center gap-2 rounded-[8px] border px-3 py-2 text-xs',
                    opt.is_correct
                        ? 'border-[#1B2CC1] bg-[#F8FAFC] font-semibold text-[#1E293B] dark:border-[#7692FF] dark:bg-[#3E4F69] dark:text-[#ABD2FA]'
                        : 'border-[#E2E8F0] text-[#475569] dark:border-[#3E4F69] dark:text-[#CBD5E1]',
                ]"
            >
                <CheckCircle2 v-if="opt.is_correct" class="h-3.5 w-3.5 shrink-0 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                <span>{{ opt.option_text }}</span>
            </div>
        </div>

        <p v-if="question.explanation" class="mt-3 rounded-[8px] bg-[#F8FAFC] px-3 py-2 text-xs text-[#475569] dark:bg-[#162030] dark:text-[#CBD5E1]">
            Pembahasan: {{ question.explanation }}
        </p>
    </article>
</template>
