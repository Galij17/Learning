<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, BookMarked, BookOpen, CheckCircle2, Copy, Edit3, FileSpreadsheet, FolderOpen, Layers, Plus, Search, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import QuestionExcelImportModal from '@/Components/QuestionExcelImportModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { PaginatedData, Question } from '@/types';

interface SubjectWithCount {
    id: number;
    name: string;
    code: string;
    category_id: number;
    questions_count?: number;
    category?: { id: number; name: string } | null;
}

const props = withDefaults(
    defineProps<{
        questions: PaginatedData<Question>;
        categories: Array<{ id: number; name: string }>;
        subjects: SubjectWithCount[];
        unassignedCount?: number;
        instructorQuizzes?: Array<{
            id: number;
            course_id: number;
            title: string;
            type: string;
            course?: { id: number; title: string; code: string };
        }>;
        filters: {
            search?: string;
            category_id?: string | number;
            subject_id?: string | number;
            type?: string;
            view?: string;
        };
    }>(),
    {
        unassignedCount: 0,
        instructorQuizzes: () => [],
    }
);

const search = ref(props.filters.search ?? '');
const categoryId = ref(String(props.filters.category_id ?? ''));
const subjectId = ref(String(props.filters.subject_id ?? ''));
const typeFilter = ref(props.filters.type ?? '');

const directorySearch = ref('');
const directoryCategoryId = ref('');

watch(
    () => props.filters,
    (next) => {
        search.value = next.search ?? '';
        categoryId.value = String(next.category_id ?? '');
        subjectId.value = String(next.subject_id ?? '');
        typeFilter.value = next.type ?? '';
    }
);

const activeSubjectParam = computed(() => String(props.filters.subject_id ?? ''));
const isUnassignedView = computed(() => activeSubjectParam.value === 'unassigned');
const isAllView = computed(() => props.filters.view === 'all');
const hasQuestionQuery = computed(() => Boolean(props.filters.search || props.filters.type || props.filters.category_id));
const isDirectoryMode = computed(() => !activeSubjectParam.value && !isAllView.value && !hasQuestionQuery.value);

const selectedSubject = computed(() => props.subjects.find((s) => String(s.id) === activeSubjectParam.value) ?? null);

const directorySubjects = computed(() => {
    const q = directorySearch.value.trim().toLowerCase();
    return props.subjects.filter((s) => {
        const matchesCat = !directoryCategoryId.value || s.category_id === Number(directoryCategoryId.value);
        const matchesQuery =
            !q ||
            s.name.toLowerCase().includes(q) ||
            s.code.toLowerCase().includes(q) ||
            (s.category?.name ?? '').toLowerCase().includes(q);
        return matchesCat && matchesQuery;
    });
});

const totalBankQuestions = computed(
    () => props.subjects.reduce((sum, s) => sum + Number(s.questions_count ?? 0), 0) + Number(props.unassignedCount ?? 0)
);

const showModal = ref(false);
const showImportModal = ref(false);
const editingQuestion = ref<Question | null>(null);
const deletingQuestionId = ref<number | null>(null);
const isDeletingQuestion = ref(false);

const selectedIds = ref<number[]>([]);
const bulkMoveSubjectId = ref<string>('');
const bulkQuizId = ref<string>('');
const showBulkDeleteConfirm = ref(false);
const isBulkProcessing = ref(false);

const allPageSelected = computed(
    () => props.questions.data.length > 0 && props.questions.data.every((q) => selectedIds.value.includes(q.id))
);

watch(
    () => props.questions.data,
    () => {
        selectedIds.value = [];
    }
);

function toggleSelectAllPage(): void {
    if (allPageSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.questions.data.map((q) => q.id);
    }
}

function duplicateQuestion(id: number): void {
    router.post(route('instructor.question-bank.duplicate', id), {}, { preserveScroll: true });
}

function runBulkMoveSubject(): void {
    if (selectedIds.value.length === 0 || !bulkMoveSubjectId.value) return;
    isBulkProcessing.value = true;
    router.post(
        route('instructor.question-bank.bulk-action'),
        {
            action: 'move_subject',
            question_ids: selectedIds.value,
            subject_id: Number(bulkMoveSubjectId.value),
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isBulkProcessing.value = false;
                bulkMoveSubjectId.value = '';
                selectedIds.value = [];
            },
        }
    );
}

function runBulkAttachQuiz(): void {
    if (selectedIds.value.length === 0 || !bulkQuizId.value) return;
    isBulkProcessing.value = true;
    router.post(
        route('instructor.question-bank.bulk-action'),
        {
            action: 'attach_to_quiz',
            question_ids: selectedIds.value,
            quiz_id: Number(bulkQuizId.value),
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isBulkProcessing.value = false;
                bulkQuizId.value = '';
                selectedIds.value = [];
            },
        }
    );
}

function confirmBulkDelete(): void {
    if (selectedIds.value.length === 0) return;
    isBulkProcessing.value = true;
    router.post(
        route('instructor.question-bank.bulk-action'),
        {
            action: 'delete',
            question_ids: selectedIds.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isBulkProcessing.value = false;
                showBulkDeleteConfirm.value = false;
                selectedIds.value = [];
            },
        }
    );
}

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

function openSubject(id: number | 'unassigned'): void {
    router.get(
        route('instructor.question-bank.index'),
        { subject_id: id },
        { preserveScroll: true }
    );
}

function openAllQuestions(): void {
    router.get(
        route('instructor.question-bank.index'),
        { view: 'all' },
        { preserveScroll: true }
    );
}

function backToDirectory(): void {
    router.get(route('instructor.question-bank.index'));
}

function applyFilter(): void {
    router.get(
        route('instructor.question-bank.index'),
        {
            search: search.value || undefined,
            category_id: categoryId.value || undefined,
            subject_id: subjectId.value || undefined,
            type: typeFilter.value || undefined,
            view: !subjectId.value ? 'all' : undefined,
        },
        { preserveState: true, replace: true }
    );
}

function openCreate(presetSubject?: SubjectWithCount | null): void {
    const targetSubject = presetSubject ?? selectedSubject.value;
    editingQuestion.value = null;
    form.reset();
    form.clearErrors();
    form.category_id = targetSubject?.category_id ?? props.categories[0]?.id ?? '';
    form.subject_id = targetSubject?.id ?? '';
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
    if (editingQuestion.value) {
        form.put(route('instructor.question-bank.update', editingQuestion.value.id), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('instructor.question-bank.store'), {
            preserveScroll: true,
            onSuccess: () => {
                if (keepOpen) {
                    resetQuestionDraft();
                } else {
                    showModal.value = false;
                    form.reset();
                }
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
        <!-- MODE 1: PILIH MATA PELAJARAN -->
        <div v-if="isDirectoryMode" class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div>
                    <h2 class="text-base font-bold">Pilih Mata Pelajaran</h2>
                    <p class="mt-0.5 text-xs text-[#475569] dark:text-[#CBD5E1]">
                        Soal di Bank Soal dapat dipakai ulang di berbagai kuis. Pilih mapel di bawah atau gunakan pencarian cepat ({{ totalBankQuestions }} total soal).
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="openAllQuestions"
                        class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] hover:bg-[#F8FAFC] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#1E293B]"
                    >
                        <Layers class="h-4 w-4" aria-hidden="true" />
                        Lihat Semua Soal ({{ totalBankQuestions }})
                    </button>
                    <button
                        type="button"
                        @click="showImportModal = true"
                        class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] hover:bg-[#F8FAFC] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#1E293B]"
                    >
                        <FileSpreadsheet class="h-4 w-4" aria-hidden="true" />
                        Import Excel
                    </button>
                    <button
                        type="button"
                        @click="openCreate(null)"
                        class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <Plus class="h-4 w-4" aria-hidden="true" />
                        Tambah Soal Cepat
                    </button>
                </div>
            </div>

            <!-- Filter Directory Mapel -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[220px]">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#64748B]" aria-hidden="true" />
                    <input
                        v-model="directorySearch"
                        type="search"
                        placeholder="Cari nama atau kode mata pelajaran..."
                        class="min-h-[42px] w-full rounded-[8px] border border-[#E2E8F0] bg-white pl-9 pr-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]"
                    />
                </div>
                <select
                    v-model="directoryCategoryId"
                    aria-label="Filter kategori mata pelajaran"
                    class="min-h-[42px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]"
                >
                    <option value="">Semua Kategori</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>

            <EmptyState
                v-if="directorySubjects.length === 0 && unassignedCount === 0"
                :icon="BookOpen"
                title="Mata pelajaran tidak ditemukan"
                description="Sesuaikan kata kunci pencarian atau hubungi admin untuk menambahkan mata pelajaran."
            />

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <button
                    v-for="subj in directorySubjects"
                    :key="subj.id"
                    type="button"
                    @click="openSubject(subj.id)"
                    class="group flex flex-col justify-between rounded-[14px] border border-[#E2E8F0] bg-white p-5 text-left transition-all hover:border-[#1B2CC1] hover:shadow-sm dark:border-[#3E4F69] dark:bg-[#283548] dark:hover:border-[#ABD2FA]"
                >
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-[6px] bg-[#E2E8F0] px-2.5 py-1 font-mono text-xs font-bold text-[#1B2CC1] dark:bg-[#162030] dark:text-[#ABD2FA]">
                                <FolderOpen class="h-3.5 w-3.5" aria-hidden="true" />
                                {{ subj.code }}
                            </span>
                            <StatusBadge v-if="subj.category" :label="subj.category.name" variant="neutral" />
                        </div>
                        <h3 class="mt-3 text-base font-bold text-[#1E293B] group-hover:text-[#1B2CC1] dark:text-[#F8FAFC] dark:group-hover:text-[#ABD2FA]">
                            {{ subj.name }}
                        </h3>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-[#E2E8F0] pt-3 text-xs dark:border-[#3E4F69]">
                        <span class="font-semibold text-[#475569] dark:text-[#CBD5E1]">
                            {{ subj.questions_count ?? 0 }} Soal
                        </span>
                        <span class="font-semibold text-[#1B2CC1] dark:text-[#ABD2FA]">
                            Kelola & Tambah Soal &rarr;
                        </span>
                    </div>
                </button>

                <button
                    v-if="unassignedCount > 0 && !directorySearch && !directoryCategoryId"
                    type="button"
                    @click="openSubject('unassigned')"
                    class="group flex flex-col justify-between rounded-[14px] border border-dashed border-[#CBD5E1] bg-white p-5 text-left transition-all hover:border-[#1B2CC1] dark:border-[#475569] dark:bg-[#283548] dark:hover:border-[#ABD2FA]"
                >
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-[6px] bg-[#F1F5F9] px-2.5 py-1 font-mono text-xs font-bold text-[#475569] dark:bg-[#162030] dark:text-[#CBD5E1]">
                                <FolderOpen class="h-3.5 w-3.5" aria-hidden="true" />
                                UMUM
                            </span>
                            <StatusBadge label="Tanpa Mapel" variant="amber" />
                        </div>
                        <h3 class="mt-3 text-base font-bold text-[#1E293B] dark:text-[#F8FAFC]">
                            Soal Umum (Belum Dipetakan)
                        </h3>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-[#E2E8F0] pt-3 text-xs dark:border-[#3E4F69]">
                        <span class="font-semibold text-[#475569] dark:text-[#CBD5E1]">
                            {{ unassignedCount }} Soal
                        </span>
                        <span class="font-semibold text-[#1B2CC1] dark:text-[#ABD2FA]">
                            Lihat Soal &rarr;
                        </span>
                    </div>
                </button>
            </div>
        </div>

        <!-- MODE 2: DI DALAM MATA PELAJARAN / DAFTAR SOAL -->
        <div v-else class="space-y-5">
            <!-- Header Mapel Terpilih -->
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="space-y-1.5">
                    <button
                        type="button"
                        @click="backToDirectory"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                        Kembali ke Daftar Mata Pelajaran
                    </button>

                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            v-if="selectedSubject"
                            class="rounded-[6px] bg-[#E2E8F0] px-2.5 py-0.5 font-mono text-xs font-bold text-[#1B2CC1] dark:bg-[#162030] dark:text-[#ABD2FA]"
                        >
                            {{ selectedSubject.code }}
                        </span>
                        <h2 class="text-lg font-bold">
                            {{ selectedSubject ? selectedSubject.name : isUnassignedView ? 'Soal Umum (Tanpa Mapel)' : 'Semua Soal Bank' }}
                        </h2>
                        <StatusBadge v-if="selectedSubject?.category" :label="selectedSubject.category.name" variant="pine" />
                        <StatusBadge :label="`${questions.total} Soal`" variant="mint" />
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="showImportModal = true"
                        class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] hover:bg-[#F8FAFC] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#1E293B]"
                    >
                        <FileSpreadsheet class="h-4 w-4" aria-hidden="true" />
                        Import Excel (.xlsx)
                    </button>
                    <button
                        type="button"
                        @click="openCreate(selectedSubject)"
                        class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <Plus class="h-4 w-4" aria-hidden="true" />
                        {{ selectedSubject ? `Tambah Soal di ${selectedSubject.name}` : 'Tambah Soal' }}
                    </button>
                </div>
            </div>

            <!-- Filters Inside Subject -->
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]">
                <form @submit.prevent="applyFilter" class="flex flex-wrap items-center gap-2 w-full">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari teks pertanyaan..."
                        class="min-h-[42px] flex-1 min-w-[200px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
                    <select
                        v-model="subjectId"
                        @change="applyFilter"
                        aria-label="Pindah mata pelajaran"
                        class="min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    >
                        <option value="">Semua Mapel</option>
                        <option v-for="s in subjects" :key="s.id" :value="String(s.id)">
                            {{ s.code }} — {{ s.name }} ({{ s.questions_count ?? 0 }})
                        </option>
                        <option v-if="unassignedCount > 0" value="unassigned">Tanpa Mapel ({{ unassignedCount }})</option>
                    </select>
                    <select v-model="typeFilter" @change="applyFilter" class="min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="">Semua Tipe</option>
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="true_false">Benar / Salah</option>
                        <option value="essay">Essay</option>
                    </select>
                    <button type="submit" class="min-h-[42px] rounded-[8px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
                        Cari
                    </button>
                </form>
            </div>

            <div v-if="questions.data.length === 0" class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 text-center dark:border-[#3E4F69] dark:bg-[#283548]">
                <EmptyState
                    :icon="BookMarked"
                    :title="selectedSubject ? `Belum ada soal di ${selectedSubject.name}` : 'Tidak ada soal yang cocok'"
                    description="Buat soal pilihan ganda, benar-salah, atau essay agar siap dipakai pada kuis dan ujian."
                />
                <button
                    type="button"
                    @click="openCreate(selectedSubject)"
                    class="mt-4 inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    {{ selectedSubject ? `Buat Soal Pertama untuk ${selectedSubject.name}` : 'Tambah Soal Baru' }}
                </button>
            </div>

            <div v-else class="space-y-4">
                <!-- Select All & Bulk Action Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[12px] border border-[#E2E8F0] bg-[#F8FAFC] px-4 py-2.5 text-xs dark:border-[#3E4F69] dark:bg-[#162030]">
                    <label class="inline-flex cursor-pointer items-center gap-2 font-semibold">
                        <input type="checkbox" :checked="allPageSelected" @change="toggleSelectAllPage" class="h-4 w-4 rounded" />
                        <span>Pilih Semua di Halaman Ini ({{ selectedIds.length }} dipilih)</span>
                    </label>

                    <div v-if="selectedIds.length > 0" class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1">
                            <select v-model="bulkMoveSubjectId" aria-label="Pindahkan ke mapel" class="min-h-[34px] rounded-[8px] border border-[#E2E8F0] bg-white px-2 text-xs dark:border-[#1B2CC1] dark:bg-[#283548]">
                                <option value="">Pindah ke Mapel...</option>
                                <option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.code }} — {{ s.name }}</option>
                            </select>
                            <button
                                v-if="bulkMoveSubjectId"
                                type="button"
                                :disabled="isBulkProcessing"
                                @click="runBulkMoveSubject"
                                class="min-h-[34px] rounded-[8px] bg-[#1B2CC1] px-2.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                            >
                                Pindahkan
                            </button>
                        </div>

                        <div v-if="instructorQuizzes.length > 0" class="flex items-center gap-1">
                            <select v-model="bulkQuizId" aria-label="Masukkan ke kuis" class="min-h-[34px] rounded-[8px] border border-[#E2E8F0] bg-white px-2 text-xs dark:border-[#1B2CC1] dark:bg-[#283548]">
                                <option value="">Masukkan ke Kuis...</option>
                                <option v-for="qz in instructorQuizzes" :key="qz.id" :value="String(qz.id)">
                                    [{{ qz.course?.code }}] {{ qz.title }}
                                </option>
                            </select>
                            <button
                                v-if="bulkQuizId"
                                type="button"
                                :disabled="isBulkProcessing"
                                @click="runBulkAttachQuiz"
                                class="min-h-[34px] rounded-[8px] bg-[#1B2CC1] px-2.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                            >
                                Tambahkan
                            </button>
                        </div>

                        <button
                            type="button"
                            :disabled="isBulkProcessing"
                            @click="showBulkDeleteConfirm = true"
                            class="inline-flex min-h-[34px] items-center gap-1 rounded-[8px] bg-red-600 px-3 text-xs font-semibold text-white hover:bg-red-700"
                        >
                            <Trash2 class="h-3.5 w-3.5" aria-hidden="true" />
                            Hapus ({{ selectedIds.length }})
                        </button>
                    </div>
                </div>

                <article
                    v-for="q in questions.data"
                    :key="q.id"
                    class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <input v-model="selectedIds" type="checkbox" :value="q.id" :aria-label="`Pilih soal #${q.id}`" class="h-4 w-4 rounded" />
                            <StatusBadge :label="typeLabel(q.type)" variant="mint" />
                            <StatusBadge :label="`${q.points} poin`" variant="neutral" />
                            <StatusBadge :label="q.difficulty" :variant="q.difficulty === 'hard' ? 'amber' : 'pine'" />
                            <span v-if="q.category || q.subject" class="text-xs text-[#475569] dark:text-[#CBD5E1]">
                                {{ q.category?.name }} <span v-if="q.subject">• {{ q.subject.name }}</span>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="duplicateQuestion(q.id)" title="Duplikat soal" aria-label="Duplikat soal" class="rounded-[8px] border border-[#E2E8F0] p-2 hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:hover:bg-[#3E4F69]">
                                <Copy class="h-4 w-4" aria-hidden="true" />
                            </button>
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
                    <button type="button" @click="showModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
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

        <QuestionExcelImportModal
            :show="showImportModal"
            :post-url="route('instructor.question-bank.import')"
            :category-id="selectedSubject?.category_id ?? (categoryId || null)"
            :subject-id="selectedSubject?.id ?? (subjectId && subjectId !== 'unassigned' ? subjectId : null)"
            :context-banner="selectedSubject ? `Soal hasil impor akan otomatis masuk ke Mapel: ${selectedSubject.code} — ${selectedSubject.name}` : 'Soal akan disimpan ke Bank Soal Anda.'"
            @close="showImportModal = false"
        />

        <ConfirmModal
            :show="Boolean(deletingQuestionId)"
            title="Hapus Soal dari Bank Soal"
            message="Hapus soal ini dari bank soal?"
            confirm-text="Hapus Soal"
            :processing="isDeletingQuestion"
            @confirm="confirmDeleteQuestion"
            @cancel="deletingQuestionId = null"
        />

        <ConfirmModal
            :show="showBulkDeleteConfirm"
            title="Hapus Soal Terpilih"
            :message="`Hapus ${selectedIds.length} soal yang dipilih dari bank soal?`"
            confirm-text="Hapus Semua Terpilih"
            :processing="isBulkProcessing"
            @confirm="confirmBulkDelete"
            @cancel="showBulkDeleteConfirm = false"
        />
    </AuthenticatedLayout>
</template>
