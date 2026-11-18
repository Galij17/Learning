<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { BookMarked, CheckCircle2, Edit3, Plus, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { PaginatedData, Question } from '@/types';

const props = defineProps<{
    questions: PaginatedData<Question>;
    categories: Array<{ id: number; name: string }>;
    subjects: Array<{ id: number; name: string; code: string; category_id: number }>;
    filters: {
        search?: string;
        category_id?: string | number;
        subject_id?: string | number;
        type?: string;
    };
}>();

const search = ref(props.filters.search ?? '');
const categoryId = ref(String(props.filters.category_id ?? ''));
const subjectId = ref(String(props.filters.subject_id ?? ''));
const typeFilter = ref(props.filters.type ?? '');

const showModal = ref(false);
const editingQuestion = ref<Question | null>(null);
const deletingQuestionId = ref<number | null>(null);
const isDeletingQuestion = ref(false);

const filterSubjects = computed(() =>
    props.subjects.filter((s) => !categoryId.value || s.category_id === Number(categoryId.value))
);

interface OptionInput {
    option_text: string;
    is_correct: boolean;
}

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
    () => form.type,
    (nextType) => {
        if (editingQuestion.value && editingQuestion.value.type === nextType) return;
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

function applyFilter(): void {
    router.get(
        route('instructor.question-bank.index'),
        {
            search: search.value || undefined,
            category_id: categoryId.value || undefined,
            subject_id: subjectId.value || undefined,
            type: typeFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function openCreate(): void {
    editingQuestion.value = null;
    form.reset();
    form.clearErrors();
    form.category_id = props.categories[0]?.id ?? '';
    form.subject_id = '';
    form.type = 'multiple_choice';
    form.points = 10;
    form.difficulty = 'medium';
    form.options = [
        { option_text: '', is_correct: true },
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
        { option_text: '', is_correct: false },
    ];
    showModal.value = true;
}

function openEdit(q: Question): void {
    editingQuestion.value = q;
    form.clearErrors();
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
    showModal.value = true;
}

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

function submit(): void {
    if (editingQuestion.value) {
        form.put(route('instructor.question-bank.update', editingQuestion.value.id), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('instructor.question-bank.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
}

function deleteQuestion(id: number): void {
    deletingQuestionId.value = id;
}

function confirmDeleteQuestion(): void {
    if (!deletingQuestionId.value) return;
    isDeletingQuestion.value = true;
    router.delete(route('instructor.question-bank.destroy', deletingQuestionId.value), {
        preserveScroll: true,
        onFinish: () => {
            isDeletingQuestion.value = false;
            deletingQuestionId.value = null;
        },
    });
}

function typeLabel(type: string): string {
    if (type === 'multiple_choice') return 'Pilihan Ganda';
    if (type === 'true_false') return 'Benar / Salah';
    return 'Essay';
}
</script>

<template>
    <Head title="Bank Soal" />

    <AuthenticatedLayout title="Bank Soal Pembelajaran">
        <div class="space-y-5">
            <!-- Filters -->
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]">
                <form @submit.prevent="applyFilter" class="flex flex-wrap items-center gap-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari teks pertanyaan..."
                        class="min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
                    <select v-model="categoryId" @change="subjectId = ''" aria-label="Filter kategori soal" class="min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <select v-model="subjectId" aria-label="Filter mata pelajaran" class="min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="">Semua Mapel</option>
                        <option v-for="s in filterSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <select v-model="typeFilter" class="min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="">Semua Tipe</option>
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="true_false">Benar / Salah</option>
                        <option value="essay">Essay</option>
                    </select>
                    <button type="submit" class="min-h-[42px] rounded-[8px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
                        Filter
                    </button>
                </form>

                <button
                    type="button"
                    @click="openCreate"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    Tambah Soal
                </button>
            </div>

            <EmptyState
                v-if="questions.data.length === 0"
                :icon="BookMarked"
                title="Bank soal masih kosong"
                description="Buat soal pilihan ganda, benar-salah, atau essay agar dapat digunakan ulang di berbagai kuis dan ujian."
            />

            <div v-else class="space-y-4">
                <article
                    v-for="q in questions.data"
                    :key="q.id"
                    class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge :label="typeLabel(q.type)" variant="mint" />
                            <StatusBadge :label="`${q.points} poin`" variant="neutral" />
                            <StatusBadge :label="q.difficulty" :variant="q.difficulty === 'hard' ? 'amber' : 'pine'" />
                            <span v-if="q.category" class="text-xs text-[#475569] dark:text-[#CBD5E1]">
                                {{ q.category.name }} <span v-if="q.subject">• {{ q.subject.name }}</span>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="openEdit(q)" aria-label="Edit soal" class="rounded-[8px] border border-[#E2E8F0] p-2 hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]">
                                <Edit3 class="h-4 w-4" aria-hidden="true" />
                            </button>
                            <button type="button" @click="deleteQuestion(q.id)" aria-label="Hapus soal" class="rounded-[8px] border border-[#FECACA] p-2 text-[#DC2626] hover:bg-[#FEE2E2] dark:border-[#7F1D1D]">
                                <Trash2 class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <p class="mt-3 text-sm font-semibold leading-relaxed">{{ q.question_text }}</p>

                    <div v-if="q.options && q.options.length > 0" class="mt-3 grid gap-2 sm:grid-cols-2">
                        <div
                            v-for="opt in q.options"
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

                    <p v-if="q.explanation" class="mt-3 rounded-[8px] bg-[#F8FAFC] px-3 py-2 text-xs text-[#475569] dark:bg-[#162030] dark:text-[#CBD5E1]">
                        Pembahasan: {{ q.explanation }}
                    </p>
                </article>
            </div>

            <PaginationBar :links="questions.links" :from="questions.from" :to="questions.to" :total="questions.total" />
        </div>

        <AppModal :show="showModal" :title="editingQuestion ? 'Edit Soal' : 'Tambah Soal Baru'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
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
                            <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
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

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="form.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        Simpan Soal
                    </button>
                </div>
            </form>
        </AppModal>

        <ConfirmModal
            :show="Boolean(deletingQuestionId)"
            title="Hapus Soal dari Bank Soal"
            message="Hapus soal ini dari bank soal?"
            confirm-text="Hapus Soal"
            :processing="isDeletingQuestion"
            @confirm="confirmDeleteQuestion"
            @cancel="deletingQuestionId = null"
        />
    </AuthenticatedLayout>
</template>
