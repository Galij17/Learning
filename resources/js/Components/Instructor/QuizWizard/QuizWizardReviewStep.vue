<script setup lang="ts">
import { ArrowLeft, Send, Trash2 } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Question } from '@/types';
import type { NewQuestionDraft } from './QuizWizardQuestionsStep.vue';

defineProps<{
    title: string;
    type: 'quiz' | 'exam';
    durationMinutes: number;
    maxAttempts: number;
    passingScore: number;
    selectedBankObjects: Question[];
    newQuestions: NewQuestionDraft[];
    totalSelectedCount: number;
    totalSelectedPoints: number;
    processing: boolean;
}>();

const emit = defineEmits<{
    removeBankQuestion: [id: number];
    removeNewQuestion: [index: number];
    back: [];
    submit: [publishNow: boolean];
}>();
</script>

<template>
    <section class="space-y-5 rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
        <div class="rounded-[12px] border border-[#E2E8F0] bg-[#F8FAFC] p-4 dark:border-[#3E4F69] dark:bg-[#162030]">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-bold">{{ title || '(Belum ada judul)' }}</h3>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                        {{ type === 'exam' ? 'Ujian' : 'Kuis' }} • Durasi {{ durationMinutes }} menit • Maks {{ maxAttempts }}x percobaan • KKM {{ passingScore }}%
                    </p>
                </div>
                <StatusBadge :label="`${totalSelectedCount} Soal • ${totalSelectedPoints} Poin`" variant="mint" />
            </div>
        </div>

        <div v-if="totalSelectedCount === 0" class="rounded-[10px] border border-amber-300 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
            Belum ada soal dipilih. Anda tetap bisa menyimpan sebagai <strong>Draft</strong> lalu mengimpor Excel atau menambah soal di halaman berikutnya.
        </div>

        <div v-else class="max-h-72 space-y-2 overflow-y-auto pr-1">
            <div
                v-for="(q, idx) in selectedBankObjects"
                :key="`b-${q.id}`"
                class="flex items-center justify-between gap-3 rounded-[10px] border border-[#E2E8F0] px-4 py-2.5 text-xs dark:border-[#3E4F69]"
            >
                <div class="min-w-0">
                    <span class="font-mono font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">#{{ idx + 1 }}</span>
                    <span class="ml-2 font-medium">{{ q.question_text }}</span>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <StatusBadge :label="`${q.points} poin`" variant="neutral" />
                    <button type="button" @click="emit('removeBankQuestion', q.id)" aria-label="Hapus dari daftar" class="text-red-600 hover:underline">
                        <Trash2 class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <div
                v-for="(nq, idx) in newQuestions"
                :key="`n-${idx}`"
                class="flex items-center justify-between gap-3 rounded-[10px] border border-[#7692FF] px-4 py-2.5 text-xs dark:border-[#ABD2FA]"
            >
                <div class="min-w-0">
                    <span class="font-mono font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">#{{ selectedBankObjects.length + idx + 1 }}</span>
                    <StatusBadge label="Baru" variant="pine" class="ml-2" />
                    <span class="ml-2 font-medium">{{ nq.question_text }}</span>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <StatusBadge :label="`${nq.points} poin`" variant="neutral" />
                    <button type="button" @click="emit('removeNewQuestion', idx)" aria-label="Hapus dari daftar" class="text-red-600 hover:underline">
                        <Trash2 class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <button type="button" @click="emit('back')" class="inline-flex min-h-[42px] items-center gap-1.5 rounded-[10px] border border-[#E2E8F0] px-4 text-xs font-semibold dark:border-[#3E4F69]">
                <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                Kembali ke Soal
            </button>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    :disabled="processing"
                    @click="emit('submit', false)"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] disabled:opacity-50 dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                >
                    Simpan sebagai Draft
                </button>
                <button
                    type="button"
                    :disabled="processing || totalSelectedCount === 0"
                    @click="emit('submit', true)"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    <Send class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ processing ? 'Menyimpan...' : 'Terbitkan ke Siswa' }}
                </button>
            </div>
        </div>
    </section>
</template>
