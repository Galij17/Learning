<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { ArrowLeft, ArrowRight, BookMarked, Plus } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Question } from '@/types';

export interface NewQuestionDraft {
    type: 'multiple_choice' | 'true_false' | 'essay';
    question_text: string;
    explanation: string;
    points: number;
    difficulty: 'easy' | 'medium' | 'hard';
    options: Array<{ option_text: string; is_correct: boolean }>;
}

const props = defineProps<{
    bankQuestions: Question[];
    subjects: Array<{ id: number; name: string; code: string; category_id: number }>;
    courseSubjectId: number | null;
    questionIds: number[];
    newQuestions: NewQuestionDraft[];
    totalSelectedCount: number;
    totalSelectedPoints: number;
}>();

const emit = defineEmits<{
    'update:questionIds': [ids: number[]];
    addNewQuestion: [question: NewQuestionDraft];
    back: [];
    next: [];
}>();

const step2Tab = ref<'bank' | 'new'>('bank');
const bankSearch = ref('');
const bankSubjectFilter = ref(
    props.courseSubjectId && props.bankQuestions.some((q) => q.subject_id === props.courseSubjectId)
        ? String(props.courseSubjectId)
        : ''
);
const bankTypeFilter = ref('');

const draft = ref<NewQuestionDraft>({
    type: 'multiple_choice',
    question_text: '',
    explanation: '',
    points: 10,
    difficulty: 'medium',
    options: [
        { option_text: '', is_correct: true },
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
    ],
});

watch(
    () => draft.value.type,
    (nextType) => {
        if (nextType === 'true_false') {
            draft.value.options = [
                { option_text: 'Benar', is_correct: true },
                { option_text: 'Salah', is_correct: false },
            ];
        } else if (nextType === 'multiple_choice' && draft.value.options.length < 2) {
            draft.value.options = [
                { option_text: '', is_correct: true },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
            ];
        }
    }
);

const filteredBank = computed(() => {
    const q = bankSearch.value.trim().toLowerCase();
    return props.bankQuestions.filter((item) => {
        if (bankSubjectFilter.value && String(item.subject_id ?? '') !== bankSubjectFilter.value) return false;
        if (bankTypeFilter.value && item.type !== bankTypeFilter.value) return false;
        if (q && !item.question_text.toLowerCase().includes(q)) return false;
        return true;
    });
});

const allFilteredSelected = computed(
    () => filteredBank.value.length > 0 && filteredBank.value.every((q) => props.questionIds.includes(q.id))
);

function toggleQuestionId(id: number): void {
    if (props.questionIds.includes(id)) {
        emit('update:questionIds', props.questionIds.filter((qId) => qId !== id));
    } else {
        emit('update:questionIds', [...props.questionIds, id]);
    }
}

function toggleSelectAllBank(): void {
    const ids = filteredBank.value.map((q) => q.id);
    if (allFilteredSelected.value) {
        const idSet = new Set(ids);
        emit('update:questionIds', props.questionIds.filter((id) => !idSet.has(id)));
    } else {
        emit('update:questionIds', Array.from(new Set([...props.questionIds, ...ids])));
    }
}

function setCorrectDraft(index: number): void {
    draft.value.options = draft.value.options.map((opt, idx) => ({
        ...opt,
        is_correct: idx === index,
    }));
}

function addDraftQuestion(): void {
    if (!draft.value.question_text.trim()) return;
    emit('addNewQuestion', {
        ...draft.value,
        question_text: draft.value.question_text.trim(),
        explanation: draft.value.explanation.trim(),
        options: draft.value.type === 'essay' ? [] : draft.value.options.map((o) => ({ ...o })),
    });
    draft.value.question_text = '';
    draft.value.explanation = '';
    if (draft.value.type !== 'true_false') {
        draft.value.options = [
            { option_text: '', is_correct: true },
            { option_text: '', is_correct: false },
            { option_text: '', is_correct: false },
            { option_text: '', is_correct: false },
        ];
    }
}
</script>

<template>
    <section class="space-y-5 rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-[10px] bg-[#F8FAFC] px-4 py-3 text-xs dark:bg-[#162030]">
            <div>
                <span class="font-bold">{{ totalSelectedCount }} soal terpilih</span> • Total bobot: <strong>{{ totalSelectedPoints }} poin</strong>
                <p class="mt-0.5 text-[11px] text-[#475569] dark:text-[#CBD5E1]">
                    Soal baru yang dibuat di sini juga otomatis tersimpan di Bank Soal agar dapat dipakai ulang.
                </p>
            </div>
            <div class="flex gap-1.5">
                <button
                    type="button"
                    @click="step2Tab = 'bank'"
                    :class="step2Tab === 'bank' ? 'bg-[#1B2CC1] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]' : 'border border-[#E2E8F0] dark:border-[#3E4F69]'"
                    class="inline-flex min-h-[36px] items-center gap-1.5 rounded-[8px] px-3 text-xs font-semibold"
                >
                    <BookMarked class="h-3.5 w-3.5" aria-hidden="true" />
                    Pilih dari Bank Soal
                </button>
                <button
                    type="button"
                    @click="step2Tab = 'new'"
                    :class="step2Tab === 'new' ? 'bg-[#1B2CC1] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]' : 'border border-[#E2E8F0] dark:border-[#3E4F69]'"
                    class="inline-flex min-h-[36px] items-center gap-1.5 rounded-[8px] px-3 text-xs font-semibold"
                >
                    <Plus class="h-3.5 w-3.5" aria-hidden="true" />
                    Buat Soal Baru ({{ newQuestions.length }})
                </button>
            </div>
        </div>

        <div v-if="step2Tab === 'bank'" class="space-y-3">
            <div class="grid gap-2 sm:grid-cols-3">
                <input v-model="bankSearch" type="search" placeholder="Cari teks pertanyaan..." class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]" />
                <select v-model="bankSubjectFilter" class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-2.5 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]">
                    <option value="">Semua Mapel</option>
                    <option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.code }} — {{ s.name }}</option>
                </select>
                <select v-model="bankTypeFilter" class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-2.5 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]">
                    <option value="">Semua Tipe</option>
                    <option value="multiple_choice">Pilihan Ganda</option>
                    <option value="true_false">Benar / Salah</option>
                    <option value="essay">Essay</option>
                </select>
            </div>

            <div v-if="filteredBank.length > 0" class="flex items-center justify-between text-xs">
                <span class="text-[#475569] dark:text-[#CBD5E1]">Menampilkan {{ filteredBank.length }} soal</span>
                <button type="button" @click="toggleSelectAllBank" class="font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                    {{ allFilteredSelected ? 'Batal Pilih Semua Tampil' : `Pilih Semua (${filteredBank.length})` }}
                </button>
            </div>

            <div v-if="filteredBank.length === 0" class="rounded-[10px] border border-dashed border-[#CBD5E1] p-6 text-center text-xs text-[#475569] dark:border-[#3E4F69]">
                Belum ada soal di Bank Soal untuk filter ini. Gunakan tab "Buat Soal Baru" atau selesaikan wizard lalu impor Excel di halaman kelola kuis.
            </div>

            <div v-else class="max-h-72 space-y-2 overflow-y-auto pr-1">
                <label
                    v-for="bq in filteredBank"
                    :key="bq.id"
                    class="flex cursor-pointer items-start gap-3 rounded-[10px] border border-[#E2E8F0] p-3 hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:hover:bg-[#162030]"
                >
                    <input
                        type="checkbox"
                        :checked="questionIds.includes(bq.id)"
                        @change="toggleQuestionId(bq.id)"
                        class="mt-1 h-4 w-4 rounded"
                    />
                    <div class="text-xs">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge :label="bq.type" variant="mint" />
                            <StatusBadge :label="`${bq.points} poin`" variant="neutral" />
                            <span v-if="bq.subject" class="text-[11px] font-semibold text-[#475569] dark:text-[#CBD5E1]">{{ bq.subject.code }} — {{ bq.subject.name }}</span>
                        </div>
                        <p class="mt-1 text-sm font-medium">{{ bq.question_text }}</p>
                    </div>
                </label>
            </div>
        </div>

        <div v-else class="space-y-4">
            <div class="space-y-3 rounded-[12px] border border-[#E2E8F0] p-4 dark:border-[#3E4F69]">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Tipe</label>
                        <select v-model="draft.type" class="w-full min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]">
                            <option value="multiple_choice">Pilihan Ganda</option>
                            <option value="true_false">Benar / Salah</option>
                            <option value="essay">Essay</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Poin</label>
                        <input v-model.number="draft.points" type="number" min="1" max="500" class="w-full min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Kesulitan</label>
                        <select v-model="draft.difficulty" class="w-full min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold">Pertanyaan</label>
                    <textarea v-model="draft.question_text" rows="2" placeholder="Tulis pertanyaan..." class="w-full rounded-[8px] border border-[#E2E8F0] p-2.5 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                </div>
                <div v-if="draft.type !== 'essay'" class="space-y-2">
                    <span class="block text-xs font-semibold">Pilihan Jawaban (pilih kunci benar)</span>
                    <div v-for="(opt, idx) in draft.options" :key="idx" class="flex items-center gap-2">
                        <input type="radio" name="wiz_correct" :checked="opt.is_correct" @change="setCorrectDraft(idx)" class="h-4 w-4" />
                        <input v-model="opt.option_text" type="text" :placeholder="`Pilihan ${idx + 1}`" class="flex-1 min-h-[36px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    </div>
                </div>
                <div class="flex justify-end">
                    <button
                        type="button"
                        :disabled="!draft.question_text.trim()"
                        @click="addDraftQuestion"
                        class="inline-flex min-h-[38px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <Plus class="h-3.5 w-3.5" aria-hidden="true" />
                        Tambahkan ke Daftar Soal Kuis
                    </button>
                </div>
            </div>
        </div>

        <div class="flex justify-between pt-2">
            <button type="button" @click="emit('back')" class="inline-flex min-h-[42px] items-center gap-1.5 rounded-[10px] border border-[#E2E8F0] px-4 text-xs font-semibold dark:border-[#3E4F69]">
                <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                Kembali
            </button>
            <button type="button" @click="emit('next')" class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                Lanjut ke Review
                <ArrowRight class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    </section>
</template>
