<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/AppModal.vue';
import InputError from '@/Components/InputError.vue';
import type { Question } from '@/types';

const props = defineProps<{
    show: boolean;
    courseId: number;
    quizId: number;
    editingQuestion: Question | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const inlineForm = useForm({
    type: 'multiple_choice' as 'multiple_choice' | 'true_false' | 'essay',
    question_text: '',
    explanation: '',
    points: 10,
    difficulty: 'medium' as 'easy' | 'medium' | 'hard',
    is_bank: true,
    options: [
        { option_text: '', is_correct: true },
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
    ],
});

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) return;
        inlineForm.clearErrors();
        if (props.editingQuestion) {
            const q = props.editingQuestion;
            inlineForm.type = q.type;
            inlineForm.question_text = q.question_text;
            inlineForm.explanation = q.explanation ?? '';
            inlineForm.points = Number(q.points);
            inlineForm.difficulty = q.difficulty;
            inlineForm.options =
                q.options && q.options.length > 0
                    ? q.options.map((o) => ({ option_text: o.option_text, is_correct: Boolean(o.is_correct) }))
                    : [
                          { option_text: '', is_correct: true },
                          { option_text: '', is_correct: false },
                      ];
        } else {
            inlineForm.reset();
        }
    }
);

watch(
    () => inlineForm.type,
    (nextType) => {
        if (props.editingQuestion && props.editingQuestion.type === nextType) return;
        if (nextType === 'true_false') {
            inlineForm.options = [
                { option_text: 'Benar', is_correct: true },
                { option_text: 'Salah', is_correct: false },
            ];
        } else if (nextType === 'multiple_choice' && inlineForm.options.length < 2) {
            inlineForm.options = [
                { option_text: '', is_correct: true },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
            ];
        }
    }
);

function setCorrectInline(index: number): void {
    inlineForm.options = inlineForm.options.map((opt, idx) => ({
        ...opt,
        is_correct: idx === index,
    }));
}

function resetInlineQuestionDraft(): void {
    inlineForm.question_text = '';
    inlineForm.explanation = '';
    inlineForm.options =
        inlineForm.type === 'true_false'
            ? [
                  { option_text: 'Benar', is_correct: true },
                  { option_text: 'Salah', is_correct: false },
              ]
            : [
                  { option_text: '', is_correct: true },
                  { option_text: '', is_correct: false },
                  { option_text: '', is_correct: false },
                  { option_text: '', is_correct: false },
              ];
}

function submitInlineQuestion(keepOpen = false): void {
    if (props.editingQuestion) {
        inlineForm.put(route('instructor.question-bank.update', props.editingQuestion.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
        return;
    }

    inlineForm.post(route('instructor.quizzes.inline-question', [props.courseId, props.quizId]), {
        preserveScroll: true,
        onSuccess: () => {
            if (keepOpen) {
                resetInlineQuestionDraft();
            } else {
                emit('close');
                inlineForm.reset();
            }
        },
    });
}
</script>

<template>
    <AppModal :show="show" :title="editingQuestion ? 'Edit Soal' : 'Buat Soal Baru di Kuis Ini'" @close="emit('close')">
        <form @submit.prevent="submitInlineQuestion(false)" class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label for="in_type" class="mb-1 block text-xs font-semibold">Tipe</label>
                    <select id="in_type" v-model="inlineForm.type" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="true_false">Benar / Salah</option>
                        <option value="essay">Essay</option>
                    </select>
                    <InputError :message="inlineForm.errors.type" />
                </div>
                <div>
                    <label for="in_pts" class="mb-1 block text-xs font-semibold">Poin</label>
                    <input id="in_pts" v-model.number="inlineForm.points" type="number" min="1" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    <InputError :message="inlineForm.errors.points" />
                </div>
                <div>
                    <label for="in_diff" class="mb-1 block text-xs font-semibold">Kesulitan</label>
                    <select id="in_diff" v-model="inlineForm.difficulty" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                    <InputError :message="inlineForm.errors.difficulty" />
                </div>
            </div>
            <div>
                <label for="in_text" class="mb-1 block text-xs font-semibold">Pertanyaan</label>
                <textarea id="in_text" v-model="inlineForm.question_text" rows="3" required class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                <InputError :message="inlineForm.errors.question_text" />
            </div>
            <div v-if="inlineForm.type !== 'essay'" class="space-y-2">
                <span class="block text-xs font-semibold">Pilihan Jawaban</span>
                <div v-for="(opt, idx) in inlineForm.options" :key="idx" class="flex items-center gap-2">
                    <input type="radio" name="inline_correct" :checked="opt.is_correct" @change="setCorrectInline(idx)" class="h-4 w-4" />
                    <input v-model="opt.option_text" type="text" required :placeholder="`Pilihan ${idx + 1}`" class="flex-1 min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                </div>
                <InputError :message="inlineForm.errors.options" />
            </div>
            <div>
                <label for="in_exp" class="mb-1 block text-xs font-semibold">Pembahasan (Opsional)</label>
                <textarea id="in_exp" v-model="inlineForm.explanation" rows="2" class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                <InputError :message="inlineForm.errors.explanation" />
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" @click="emit('close')" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                <button
                    v-if="!editingQuestion"
                    type="button"
                    :disabled="inlineForm.processing"
                    @click="submitInlineQuestion(true)"
                    class="min-h-[40px] rounded-[8px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] disabled:opacity-50 dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                >
                    Simpan &amp; Tambah Soal Lagi
                </button>
                <button type="submit" :disabled="inlineForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    {{ editingQuestion ? 'Simpan Perubahan' : 'Simpan & Tutup' }}
                </button>
            </div>
        </form>
    </AppModal>
</template>
