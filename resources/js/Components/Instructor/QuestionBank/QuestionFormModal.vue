<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import AppModal from '@/Components/AppModal.vue';
import InputError from '@/Components/InputError.vue';
import type { Question } from '@/types';
import type { SubjectWithCount } from './SubjectDirectoryView.vue';

interface OptionInput {
    option_text: string;
    is_correct: boolean;
}

const props = defineProps<{
    show: boolean;
    editingQuestion: Question | null;
    presetSubject: SubjectWithCount | null;
    selectedSubject: SubjectWithCount | null;
    categories: Array<{ id: number; name: string }>;
    subjects: SubjectWithCount[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const form = useForm({
    category_id: '' as number | string,
    subject_id: '' as number | string,
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
    ] as OptionInput[],
});

const filteredSubjects = computed(() =>
    props.subjects.filter((s) => !form.category_id || s.category_id === Number(form.category_id))
);

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) return;
        form.clearErrors();
        if (props.editingQuestion) {
            const q = props.editingQuestion;
            form.category_id = q.category_id ?? '';
            form.subject_id = q.subject_id ?? '';
            form.type = q.type;
            form.question_text = q.question_text;
            form.explanation = q.explanation ?? '';
            form.points = Number(q.points);
            form.difficulty = q.difficulty;
            form.options =
                q.options && q.options.length > 0
                    ? q.options.map((o) => ({ option_text: o.option_text, is_correct: Boolean(o.is_correct) }))
                    : [
                          { option_text: '', is_correct: true },
                          { option_text: '', is_correct: false },
                      ];
        } else {
            const target = props.presetSubject ?? props.selectedSubject;
            form.reset();
            form.category_id = target?.category_id ?? props.categories[0]?.id ?? '';
            form.subject_id = target?.id ?? '';
            form.type = 'multiple_choice';
            form.points = 10;
            form.difficulty = 'medium';
            form.options = [
                { option_text: '', is_correct: true },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
            ];
        }
    }
);

watch(
    () => form.category_id,
    () => {
        if (form.subject_id && !filteredSubjects.value.some((s) => Number(s.id) === Number(form.subject_id))) {
            form.subject_id = '';
        }
    }
);

watch(
    () => form.type,
    (nextType) => {
        if (props.editingQuestion && props.editingQuestion.type === nextType) return;
        if (nextType === 'true_false') {
            form.options = [
                { option_text: 'Benar', is_correct: true },
                { option_text: 'Salah', is_correct: false },
            ];
        } else if (nextType === 'multiple_choice' && form.options.length < 2) {
            form.options = [
                { option_text: '', is_correct: true },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
            ];
        }
    }
);

function setCorrectOption(index: number): void {
    form.options = form.options.map((opt, idx) => ({
        ...opt,
        is_correct: idx === index,
    }));
}

function addOption(): void {
    if (form.options.length >= 6) return;
    form.options.push({ option_text: '', is_correct: false });
}

function removeOption(index: number): void {
    if (form.options.length <= 2) return;
    const wasCorrect = form.options[index].is_correct;
    form.options.splice(index, 1);
    if (wasCorrect && form.options.length > 0) {
        form.options[0].is_correct = true;
    }
}

function resetQuestionDraft(): void {
    form.question_text = '';
    form.explanation = '';
    form.options =
        form.type === 'true_false'
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

function submit(keepOpen = false): void {
    if (props.editingQuestion) {
        form.put(route('instructor.question-bank.update', props.editingQuestion.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('instructor.question-bank.store'), {
            preserveScroll: true,
            onSuccess: () => {
                if (keepOpen) {
                    resetQuestionDraft();
                } else {
                    emit('close');
                    form.reset();
                }
            },
        });
    }
}
</script>

<template>
    <AppModal :show="show" :title="editingQuestion ? 'Edit Soal' : 'Tambah Soal Baru'" @close="emit('close')">
        <form @submit.prevent="submit(false)" class="space-y-4">
            <div
                v-if="!editingQuestion && selectedSubject"
                class="rounded-[8px] border border-[#7692FF] bg-[#F8FAFC] px-3 py-2 text-xs font-medium text-[#1B2CC1] dark:bg-[#162030] dark:text-[#ABD2FA]"
            >
                Menambahkan ke Mapel: <strong>{{ selectedSubject.code }} — {{ selectedSubject.name }}</strong>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="qb_cat" class="mb-1 block text-xs font-semibold">Kategori</label>
                    <select id="qb_cat" v-model="form.category_id" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="">Umum</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <InputError :message="form.errors.category_id" />
                </div>
                <div>
                    <label for="qb_sub" class="mb-1 block text-xs font-semibold">Mata Pelajaran</label>
                    <select id="qb_sub" v-model="form.subject_id" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="">Pilih Mata Pelajaran</option>
                        <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.code }} — {{ s.name }}</option>
                    </select>
                    <InputError :message="form.errors.subject_id" />
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label for="qb_type" class="mb-1 block text-xs font-semibold">Tipe Soal</label>
                    <select id="qb_type" v-model="form.type" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="true_false">Benar / Salah</option>
                        <option value="essay">Essay</option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>
                <div>
                    <label for="qb_pts" class="mb-1 block text-xs font-semibold">Bobot Poin</label>
                    <input id="qb_pts" v-model.number="form.points" type="number" min="1" max="500" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    <InputError :message="form.errors.points" />
                </div>
                <div>
                    <label for="qb_diff" class="mb-1 block text-xs font-semibold">Tingkat Kesulitan</label>
                    <select id="qb_diff" v-model="form.difficulty" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                    <InputError :message="form.errors.difficulty" />
                </div>
            </div>

            <div>
                <label for="qb_text" class="mb-1 block text-xs font-semibold">Pertanyaan</label>
                <textarea id="qb_text" v-model="form.question_text" rows="3" required class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                <InputError :message="form.errors.question_text" />
            </div>

            <div v-if="form.type !== 'essay'" class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold">Pilihan Jawaban (pilih 1 kunci benar)</span>
                    <button v-if="form.type === 'multiple_choice' && form.options.length < 6" type="button" @click="addOption" class="text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                        + Tambah Pilihan
                    </button>
                </div>
                <div v-for="(opt, idx) in form.options" :key="idx" class="flex items-center gap-2">
                    <input
                        type="radio"
                        name="correct_option"
                        :checked="opt.is_correct"
                        @change="setCorrectOption(idx)"
                        :aria-label="`Tandai pilihan ${idx + 1} sebagai benar`"
                        class="h-4 w-4"
                    />
                    <input
                        v-model="opt.option_text"
                        type="text"
                        required
                        :placeholder="`Pilihan ${idx + 1}`"
                        class="flex-1 min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
                    <button
                        v-if="form.type === 'multiple_choice' && form.options.length > 2"
                        type="button"
                        @click="removeOption(idx)"
                        aria-label="Hapus pilihan"
                        class="rounded p-1.5 text-[#DC2626] hover:bg-[#FEE2E2]"
                    >
                        <Trash2 class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
                <p v-if="form.errors.options" class="text-xs text-[#DC2626]">{{ form.errors.options }}</p>
            </div>

            <div>
                <label for="qb_exp" class="mb-1 block text-xs font-semibold">Pembahasan (Opsional)</label>
                <textarea id="qb_exp" v-model="form.explanation" rows="2" class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                <InputError :message="form.errors.explanation" />
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" @click="emit('close')" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                <button
                    v-if="!editingQuestion"
                    type="button"
                    :disabled="form.processing"
                    @click="submit(true)"
                    class="min-h-[40px] rounded-[8px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] disabled:opacity-50 dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                >
                    Simpan &amp; Tambah Lagi
                </button>
                <button type="submit" :disabled="form.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    {{ editingQuestion ? 'Simpan Perubahan' : 'Simpan & Tutup' }}
                </button>
            </div>
        </form>
    </AppModal>
</template>
