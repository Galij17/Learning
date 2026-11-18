<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    BookMarked,
    CheckCircle2,
    ClipboardEdit,
    Clock,
    Copy,
    Edit3,
    Eye,
    FileSpreadsheet,
    HelpCircle,
    Plus,
    Settings,
    Shuffle,
    Trash2,
    Users,
} from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import QuestionExcelImportModal from '@/Components/QuestionExcelImportModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { toDateTimeLocalInput } from '@/utils/format';
import type { PaginatedData, Question, Quiz, QuizAttempt } from '@/types';

const props = withDefaults(
    defineProps<{
        course: { id: number; title: string; code: string; category_id: number; subject_id: number | null };
        quiz: Quiz;
        attempts: PaginatedData<QuizAttempt>;
        bankQuestions: Question[];
        subjects?: Array<{ id: number; name: string; code: string; category_id: number }>;
        otherQuizzes?: Array<{
            id: number;
            course_id: number;
            title: string;
            type: string;
            questions_count?: number;
            course?: { id: number; title: string; code: string };
        }>;
        analytics: {
            total_attempts: number;
            passed_count: number;
            average_score: number;
            needs_grading_count: number;
        };
    }>(),
    {
        subjects: () => [],
        otherQuizzes: () => [],
    }
);

const showBankModal = ref(false);
const showImportModal = ref(false);
const showInlineModal = ref(false);
const showSettingsModal = ref(false);
const showPreviewModal = ref(false);
const previewIndex = ref(0);
const previewShowAnswers = ref(false);
const editingQuizQuestion = ref<Question | null>(null);
const gradingAttempt = ref<QuizAttempt | null>(null);
const detachingQuestionId = ref<number | null>(null);
const isDetaching = ref(false);
const essayError = ref<string | null>(null);

const pointDrafts = ref<Record<number, number>>({});
watch(
    () => props.quiz.questions,
    (list) => {
        const next: Record<number, number> = {};
        for (const q of list ?? []) {
            next[q.id] = Number((q as Question & { pivot?: { points?: number | string } }).pivot?.points ?? q.points);
        }
        pointDrafts.value = next;
    },
    { immediate: true, deep: true }
);

const totalQuizPoints = computed(() =>
    (props.quiz.questions ?? []).reduce(
        (sum, q) => sum + Number((q as Question & { pivot?: { points?: number | string } }).pivot?.points ?? q.points),
        0
    )
);

const previewQuestion = computed(() => (props.quiz.questions ?? [])[previewIndex.value] ?? null);

const bankModalTab = ref<'manual' | 'autopick' | 'clone'>('manual');
const selectedBankIds = ref<number[]>([]);
const bankSearch = ref('');
const bankSubjectFilter = ref('');
const bankTypeFilter = ref('');
const bankDifficultyFilter = ref('');

const autoPickForm = useForm({
    count: 10,
    subject_id: props.course.subject_id ?? ('' as number | string),
    type: '',
    difficulty: '',
});

const cloneForm = useForm({
    source_quiz_id: props.otherQuizzes[0]?.id ?? ('' as number | string),
});

const filteredBankQuestions = computed(() => {
    const q = bankSearch.value.trim().toLowerCase();
    return props.bankQuestions.filter((item) => {
        if (bankSubjectFilter.value && String(item.subject_id ?? '') !== bankSubjectFilter.value) return false;
        if (bankTypeFilter.value && item.type !== bankTypeFilter.value) return false;
        if (bankDifficultyFilter.value && item.difficulty !== bankDifficultyFilter.value) return false;
        if (q && !item.question_text.toLowerCase().includes(q)) return false;
        return true;
    });
});

const allFilteredSelected = computed(
    () =>
        filteredBankQuestions.value.length > 0 &&
        filteredBankQuestions.value.every((q) => selectedBankIds.value.includes(q.id))
);

const autoPickMatchingCount = computed(
    () =>
        props.bankQuestions.filter((item) => {
            if (autoPickForm.subject_id && Number(item.subject_id) !== Number(autoPickForm.subject_id)) return false;
            if (autoPickForm.type && item.type !== autoPickForm.type) return false;
            if (autoPickForm.difficulty && item.difficulty !== autoPickForm.difficulty) return false;
            return true;
        }).length
);

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

const settingsForm = useForm({
    lesson_id: props.quiz.lesson_id ?? ('' as number | string),
    title: props.quiz.title,
    description: props.quiz.description ?? '',
    type: props.quiz.type,
    duration_minutes: props.quiz.duration_minutes,
    starts_at: toDateTimeLocalInput(props.quiz.starts_at),
    ends_at: toDateTimeLocalInput(props.quiz.ends_at),
    max_attempts: props.quiz.max_attempts,
    passing_score: Number(props.quiz.passing_score),
    shuffle_questions: props.quiz.shuffle_questions,
    shuffle_options: props.quiz.shuffle_options,
    show_results: props.quiz.show_results,
    show_explanations: props.quiz.show_explanations,
    is_published: props.quiz.is_published,
});

interface EssayGradeRow {
    answer_id: number;
    question_text: string;
    max_points: number;
    essay_answer: string;
    score: number;
    feedback: string;
}

const essayRows = ref<EssayGradeRow[]>([]);
const essaySubmitting = ref(false);

watch(
    () => inlineForm.type,
    (nextType) => {
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

function openBankModal(initialTab: 'manual' | 'autopick' | 'clone' = 'manual'): void {
    bankModalTab.value = initialTab;
    bankSearch.value = '';
    bankTypeFilter.value = '';
    bankDifficultyFilter.value = '';

    const defaultSubj = props.course.subject_id ? String(props.course.subject_id) : '';
    const hasMatchesForCourseSubject =
        defaultSubj !== '' && props.bankQuestions.some((item) => String(item.subject_id ?? '') === defaultSubj);
    bankSubjectFilter.value = hasMatchesForCourseSubject ? defaultSubj : '';

    autoPickForm.clearErrors();
    autoPickForm.subject_id = hasMatchesForCourseSubject && props.course.subject_id ? props.course.subject_id : '';
    cloneForm.clearErrors();
    if (!cloneForm.source_quiz_id && props.otherQuizzes.length > 0) {
        cloneForm.source_quiz_id = props.otherQuizzes[0].id;
    }

    showBankModal.value = true;
}

function toggleSelectAllFiltered(): void {
    const visibleIds = filteredBankQuestions.value.map((q) => q.id);
    if (allFilteredSelected.value) {
        const visibleSet = new Set(visibleIds);
        selectedBankIds.value = selectedBankIds.value.filter((id) => !visibleSet.has(id));
    } else {
        const merged = new Set([...selectedBankIds.value, ...visibleIds]);
        selectedBankIds.value = Array.from(merged);
    }
}

function submitBankAttach(): void {
    if (selectedBankIds.value.length === 0) return;
    router.post(
        route('instructor.quizzes.attach-questions', [props.course.id, props.quiz.id]),
        { question_ids: selectedBankIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedBankIds.value = [];
                showBankModal.value = false;
            },
        }
    );
}

function submitAutoPick(): void {
    autoPickForm.post(route('instructor.quizzes.auto-pick', [props.course.id, props.quiz.id]), {
        preserveScroll: true,
        onSuccess: () => {
            showBankModal.value = false;
        },
    });
}

function submitCloneQuiz(): void {
    if (!cloneForm.source_quiz_id) return;
    cloneForm.post(route('instructor.quizzes.clone-questions', [props.course.id, props.quiz.id]), {
        preserveScroll: true,
        onSuccess: () => {
            showBankModal.value = false;
        },
    });
}

function openInlineModal(): void {
    editingQuizQuestion.value = null;
    inlineForm.reset();
    inlineForm.clearErrors();
    showInlineModal.value = true;
}

function openInlineEdit(q: Question): void {
    editingQuizQuestion.value = q;
    inlineForm.clearErrors();
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
    showInlineModal.value = true;
}

function moveQuestion(index: number, delta: -1 | 1): void {
    const list = [...(props.quiz.questions ?? [])];
    const target = index + delta;
    if (target < 0 || target >= list.length) return;
    [list[index], list[target]] = [list[target], list[index]];
    router.post(
        route('instructor.quizzes.reorder-questions', [props.course.id, props.quiz.id]),
        { question_ids: list.map((q) => q.id) },
        { preserveScroll: true }
    );
}

function saveQuestionPoints(questionId: number): void {
    const pts = pointDrafts.value[questionId];
    if (!pts || pts < 1) return;
    router.patch(
        route('instructor.quizzes.question-points', [props.course.id, props.quiz.id, questionId]),
        { points: pts },
        { preserveScroll: true }
    );
}

function openStudentPreview(): void {
    previewIndex.value = 0;
    previewShowAnswers.value = false;
    showPreviewModal.value = true;
}

function openSettingsModal(): void {
    settingsForm.clearErrors();
    settingsForm.lesson_id = props.quiz.lesson_id ?? '';
    settingsForm.title = props.quiz.title;
    settingsForm.description = props.quiz.description ?? '';
    settingsForm.type = props.quiz.type;
    settingsForm.duration_minutes = props.quiz.duration_minutes;
    settingsForm.starts_at = toDateTimeLocalInput(props.quiz.starts_at);
    settingsForm.ends_at = toDateTimeLocalInput(props.quiz.ends_at);
    settingsForm.max_attempts = props.quiz.max_attempts;
    settingsForm.passing_score = Number(props.quiz.passing_score);
    settingsForm.shuffle_questions = props.quiz.shuffle_questions;
    settingsForm.shuffle_options = props.quiz.shuffle_options;
    settingsForm.show_results = props.quiz.show_results;
    settingsForm.show_explanations = props.quiz.show_explanations;
    settingsForm.is_published = props.quiz.is_published;
    showSettingsModal.value = true;
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
    if (editingQuizQuestion.value) {
        inlineForm.put(route('instructor.question-bank.update', editingQuizQuestion.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showInlineModal.value = false;
                editingQuizQuestion.value = null;
            },
        });
        return;
    }

    inlineForm.post(route('instructor.quizzes.inline-question', [props.course.id, props.quiz.id]), {
        preserveScroll: true,
        onSuccess: () => {
            if (keepOpen) {
                resetInlineQuestionDraft();
            } else {
                showInlineModal.value = false;
                inlineForm.reset();
            }
        },
    });
}

function detachQuestion(questionId: number): void {
    detachingQuestionId.value = questionId;
}

function confirmDetachQuestion(): void {
    if (!detachingQuestionId.value) return;
    isDetaching.value = true;
    router.delete(route('instructor.quizzes.detach-question', [props.course.id, props.quiz.id, detachingQuestionId.value]), {
        preserveScroll: true,
        onFinish: () => {
            isDetaching.value = false;
            detachingQuestionId.value = null;
        },
    });
}

function submitSettings(): void {
    settingsForm.put(route('instructor.quizzes.update', [props.course.id, props.quiz.id]), {
        onSuccess: () => {
            showSettingsModal.value = false;
        },
    });
}

function openEssayGrading(attempt: QuizAttempt): void {
    essayError.value = null;
    gradingAttempt.value = attempt;
    essayRows.value = (attempt.answers ?? [])
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

function submitEssayGrades(): void {
    if (!gradingAttempt.value || essayRows.value.length === 0) return;
    essaySubmitting.value = true;
    essayError.value = null;
    router.post(
        route('instructor.quizzes.grade-essays', [props.course.id, props.quiz.id, gradingAttempt.value.id]),
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
            onSuccess: () => {
                gradingAttempt.value = null;
            },
        }
    );
}
</script>

<template>
    <Head :title="quiz.title" />

    <AuthenticatedLayout :title="quiz.title">
        <div class="space-y-6">
            <!-- Header -->
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Link :href="route('instructor.courses.show', course.id)" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                            <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                            Kembali ke {{ course.title }}
                        </Link>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-bold">{{ quiz.title }}</h2>
                            <StatusBadge :label="quiz.type === 'exam' ? 'Ujian' : 'Kuis'" variant="pine" />
                            <StatusBadge :label="quiz.is_published ? 'Publik' : 'Draft'" :variant="quiz.is_published ? 'mint' : 'amber'" />
                        </div>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            Durasi {{ quiz.duration_minutes }} menit • Maks {{ quiz.max_attempts }}x percobaan • KKM {{ quiz.passing_score }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="quiz.questions && quiz.questions.length > 0"
                            type="button"
                            @click="openStudentPreview"
                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#E2E8F0] px-3.5 text-xs font-semibold hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:hover:bg-[#162030]"
                        >
                            <Eye class="h-4 w-4" aria-hidden="true" />
                            Pratinjau Siswa
                        </button>
                        <button type="button" @click="openSettingsModal" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
                            <Settings class="h-4 w-4" aria-hidden="true" />
                            Pengaturan
                        </button>
                        <button type="button" @click="openBankModal('manual')" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
                            <BookMarked class="h-4 w-4" aria-hidden="true" />
                            Ambil / Acak dari Bank Soal
                        </button>
                        <button type="button" @click="showImportModal = true" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
                            <FileSpreadsheet class="h-4 w-4" aria-hidden="true" />
                            Import Excel (.xlsx)
                        </button>
                        <button type="button" @click="openInlineModal" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                            <Plus class="h-4 w-4" aria-hidden="true" />
                            Buat Soal Baru
                        </button>
                    </div>
                </div>

                <!-- Analytics Row -->
                <div class="mt-5 grid gap-3 sm:grid-cols-4">
                    <div class="rounded-[10px] bg-[#F8FAFC] p-3.5 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Total Percobaan</p>
                        <p class="mt-1 text-xl font-bold">{{ analytics.total_attempts }}</p>
                    </div>
                    <div class="rounded-[10px] bg-[#F8FAFC] p-3.5 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Lulus KKM</p>
                        <p class="mt-1 text-xl font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">{{ analytics.passed_count }}</p>
                    </div>
                    <div class="rounded-[10px] bg-[#F8FAFC] p-3.5 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Rata-rata Skor</p>
                        <p class="mt-1 text-xl font-bold">{{ analytics.average_score }}%</p>
                    </div>
                    <div class="rounded-[10px] bg-[#F8FAFC] p-3.5 dark:bg-[#162030]">
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Perlu Nilai Essay</p>
                        <p class="mt-1 text-xl font-bold text-[#B45309] dark:text-[#FBBF24]">{{ analytics.needs_grading_count }}</p>
                    </div>
                </div>
            </section>

            <!-- Questions List -->
            <section class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-base font-bold">Daftar Soal ({{ quiz.questions?.length ?? 0 }} • Total {{ totalQuizPoints }} poin)</h3>
                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">
                        Soal terhubung dengan Bank Soal. Perubahan bobot poin di bawah berlaku khusus untuk kuis ini.
                    </p>
                </div>
                <EmptyState
                    v-if="!quiz.questions || quiz.questions.length === 0"
                    :icon="HelpCircle"
                    title="Belum ada soal pada kuis ini"
                    description="Ambil soal dari Bank Soal, impor file Excel (.xlsx), atau buat soal baru agar siswa dapat mengerjakan."
                />

                <div v-else class="space-y-3">
                    <article
                        v-for="(q, index) in quiz.questions"
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
                                        @click="saveQuestionPoints(q.id)"
                                        class="rounded bg-[#EFF6FF] px-1.5 py-0.5 text-[11px] font-semibold text-[#1B2CC1] hover:bg-[#DBEAFE] dark:bg-[#162030] dark:text-[#ABD2FA]"
                                    >
                                        Simpan
                                    </button>
                                </label>
                                <button
                                    type="button"
                                    :disabled="index === 0"
                                    @click="moveQuestion(index, -1)"
                                    aria-label="Naikkan urutan soal"
                                    class="rounded-[8px] border border-[#E2E8F0] p-1.5 disabled:opacity-35 dark:border-[#3E4F69]"
                                >
                                    <ArrowUp class="h-3.5 w-3.5" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                    :disabled="index === (quiz.questions?.length ?? 1) - 1"
                                    @click="moveQuestion(index, 1)"
                                    aria-label="Turunkan urutan soal"
                                    class="rounded-[8px] border border-[#E2E8F0] p-1.5 disabled:opacity-35 dark:border-[#3E4F69]"
                                >
                                    <ArrowDown class="h-3.5 w-3.5" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                    @click="openInlineEdit(q)"
                                    aria-label="Edit isi soal"
                                    class="rounded-[8px] border border-[#E2E8F0] p-1.5 hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]"
                                >
                                    <Edit3 class="h-3.5 w-3.5" aria-hidden="true" />
                                </button>
                                <button type="button" @click="detachQuestion(q.id)" aria-label="Lepas soal" class="rounded-[8px] border border-[#FECACA] p-1.5 text-[#DC2626] hover:bg-[#FEE2E2] dark:border-[#7F1D1D]">
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

            <!-- Attempts & Essay Grading -->
            <section class="space-y-3">
                <h3 class="text-base font-bold">Riwayat Percobaan Siswa</h3>
                <EmptyState v-if="attempts.data.length === 0" :icon="Users" title="Belum ada siswa mengerjakan" description="Hasil pengerjaan kuis atau ujian siswa akan muncul di tabel ini." />

                <div v-else class="overflow-x-auto rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#162030] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3">Siswa</th>
                                <th class="px-4 py-3">Percobaan</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Objektif</th>
                                <th class="px-4 py-3">Essay</th>
                                <th class="px-4 py-3">Nilai (%)</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="att in attempts.data" :key="att.id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ att.student?.name }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ att.student?.identity_number || att.student?.email }}</p>
                                </td>
                                <td class="px-4 py-3">#{{ att.attempt_number }}</td>
                                <td class="px-4 py-3">
                                    <StatusBadge
                                        :label="att.needs_manual_grading ? 'Perlu Nilai Essay' : att.status"
                                        :variant="att.needs_manual_grading ? 'amber' : att.is_passed ? 'mint' : 'danger'"
                                    />
                                </td>
                                <td class="px-4 py-3">{{ att.objective_score }}</td>
                                <td class="px-4 py-3">{{ att.essay_score }}</td>
                                <td class="px-4 py-3 font-bold">{{ Number(att.percentage).toFixed(1) }}%</td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        v-if="att.answers && att.answers.some((a) => a.question?.type === 'essay')"
                                        type="button"
                                        @click="openEssayGrading(att)"
                                        class="inline-flex min-h-[34px] items-center gap-1 rounded-[8px] bg-[#1B2CC1] px-3 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                                    >
                                        <ClipboardEdit class="h-3.5 w-3.5" aria-hidden="true" />
                                        Nilai Essay
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <PaginationBar :links="attempts.links" :from="attempts.from" :to="attempts.to" :total="attempts.total" />
            </section>
        </div>

        <!-- MODAL: SMART BANK PICKER (MANUAL BULK + AUTO RANDOM + CLONE) -->
        <AppModal :show="showBankModal" title="Tambahkan Soal dari Bank / Kuis Lain" max-width="xl" @close="showBankModal = false">
            <div class="space-y-4">
                <!-- Mode Tabs -->
                <div class="flex flex-wrap gap-1.5 rounded-[10px] bg-[#F1F5F9] p-1 dark:bg-[#162030]">
                    <button
                        type="button"
                        @click="bankModalTab = 'manual'"
                        :class="[
                            'inline-flex flex-1 min-h-[38px] items-center justify-center gap-1.5 rounded-[8px] px-3 text-xs font-semibold transition-colors',
                            bankModalTab === 'manual'
                                ? 'bg-white text-[#1B2CC1] shadow-sm dark:bg-[#283548] dark:text-[#ABD2FA]'
                                : 'text-[#475569] hover:text-[#1E293B] dark:text-[#CBD5E1]',
                        ]"
                    >
                        <BookMarked class="h-3.5 w-3.5" aria-hidden="true" />
                        Pilih Massal ({{ bankQuestions.length }})
                    </button>
                    <button
                        type="button"
                        @click="bankModalTab = 'autopick'"
                        :class="[
                            'inline-flex flex-1 min-h-[38px] items-center justify-center gap-1.5 rounded-[8px] px-3 text-xs font-semibold transition-colors',
                            bankModalTab === 'autopick'
                                ? 'bg-white text-[#1B2CC1] shadow-sm dark:bg-[#283548] dark:text-[#ABD2FA]'
                                : 'text-[#475569] hover:text-[#1E293B] dark:text-[#CBD5E1]',
                        ]"
                    >
                        <Shuffle class="h-3.5 w-3.5" aria-hidden="true" />
                        Ambil Acak Otomatis
                    </button>
                    <button
                        type="button"
                        @click="bankModalTab = 'clone'"
                        :class="[
                            'inline-flex flex-1 min-h-[38px] items-center justify-center gap-1.5 rounded-[8px] px-3 text-xs font-semibold transition-colors',
                            bankModalTab === 'clone'
                                ? 'bg-white text-[#1B2CC1] shadow-sm dark:bg-[#283548] dark:text-[#ABD2FA]'
                                : 'text-[#475569] hover:text-[#1E293B] dark:text-[#CBD5E1]',
                        ]"
                    >
                        <Copy class="h-3.5 w-3.5" aria-hidden="true" />
                        Salin dari Kuis Lain
                    </button>
                </div>

                <!-- TAB 1: FILTERED BULK PICKER -->
                <div v-if="bankModalTab === 'manual'" class="space-y-3">
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <input
                            v-model="bankSearch"
                            type="search"
                            placeholder="Cari teks pertanyaan..."
                            class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]"
                        />
                        <select
                            v-model="bankSubjectFilter"
                            aria-label="Filter mapel bank soal"
                            class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-2.5 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]"
                        >
                            <option value="">Semua Mapel</option>
                            <option v-for="s in subjects" :key="s.id" :value="String(s.id)">
                                {{ s.code }} — {{ s.name }}
                            </option>
                        </select>
                        <select
                            v-model="bankTypeFilter"
                            aria-label="Filter tipe soal"
                            class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-2.5 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="multiple_choice">Pilihan Ganda</option>
                            <option value="true_false">Benar / Salah</option>
                            <option value="essay">Essay</option>
                        </select>
                        <select
                            v-model="bankDifficultyFilter"
                            aria-label="Filter kesulitan"
                            class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-2.5 text-xs dark:border-[#1B2CC1] dark:bg-[#162030]"
                        >
                            <option value="">Semua Kesulitan</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>

                    <div v-if="filteredBankQuestions.length > 0" class="flex items-center justify-between rounded-[8px] bg-[#F8FAFC] px-3 py-2 text-xs dark:bg-[#162030]">
                        <span class="text-[#475569] dark:text-[#CBD5E1]">
                            Menampilkan <strong>{{ filteredBankQuestions.length }}</strong> soal tersedia • Terpilih: <strong>{{ selectedBankIds.length }}</strong>
                        </span>
                        <button
                            type="button"
                            @click="toggleSelectAllFiltered"
                            class="font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                        >
                            {{ allFilteredSelected ? 'Batal Pilih Semua Tampil' : `Pilih Semua (${filteredBankQuestions.length})` }}
                        </button>
                    </div>

                    <p v-if="filteredBankQuestions.length === 0" class="rounded-[10px] border border-dashed border-[#CBD5E1] p-6 text-center text-xs text-[#475569] dark:border-[#3E4F69] dark:text-[#CBD5E1]">
                        Tidak ada soal yang cocok dengan filter saat ini atau seluruh soal sudah masuk ke kuis ini.
                    </p>

                    <div v-else class="max-h-72 space-y-2 overflow-y-auto pr-1">
                        <label
                            v-for="bq in filteredBankQuestions"
                            :key="bq.id"
                            class="flex cursor-pointer items-start gap-3 rounded-[10px] border border-[#E2E8F0] p-3 hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:hover:bg-[#162030]"
                        >
                            <input v-model="selectedBankIds" type="checkbox" :value="bq.id" class="mt-1 h-4 w-4 rounded" />
                            <div class="text-xs">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-bold uppercase text-[#1B2CC1] dark:text-[#ABD2FA]">{{ bq.type }}</span>
                                    <span>• {{ bq.points }} poin</span>
                                    <span>• {{ bq.difficulty }}</span>
                                    <span v-if="bq.subject" class="rounded bg-[#E2E8F0] px-1.5 py-0.5 text-[11px] font-semibold dark:bg-[#283548]">
                                        {{ bq.subject.code }} — {{ bq.subject.name }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm font-medium">{{ bq.question_text }}</p>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="showBankModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Tutup</button>
                        <button
                            type="button"
                            :disabled="selectedBankIds.length === 0"
                            @click="submitBankAttach"
                            class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            Tambahkan ({{ selectedBankIds.length }})
                        </button>
                    </div>
                </div>

                <!-- TAB 2: AUTO-PICK RANDOM GENERATOR -->
                <form v-else-if="bankModalTab === 'autopick'" @submit.prevent="submitAutoPick" class="space-y-4">
                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">
                        Sistem akan memilih soal secara acak dari bank soal yang belum terpasang di kuis ini, lalu langsung memasukkannya ke daftar soal.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="ap_subj" class="mb-1 block text-xs font-semibold">Mata Pelajaran Sumber</label>
                            <select id="ap_subj" v-model="autoPickForm.subject_id" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                                <option value="">Semua Mata Pelajaran</option>
                                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.code }} — {{ s.name }}</option>
                            </select>
                            <InputError :message="autoPickForm.errors.subject_id" />
                        </div>
                        <div>
                            <label for="ap_count" class="mb-1 block text-xs font-semibold">Jumlah Soal yang Diambil</label>
                            <input id="ap_count" v-model.number="autoPickForm.count" type="number" min="1" max="100" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                            <InputError :message="autoPickForm.errors.count" />
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="ap_type" class="mb-1 block text-xs font-semibold">Filter Tipe Soal (Opsional)</label>
                            <select id="ap_type" v-model="autoPickForm.type" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                                <option value="">Campuran (Semua Tipe)</option>
                                <option value="multiple_choice">Pilihan Ganda</option>
                                <option value="true_false">Benar / Salah</option>
                                <option value="essay">Essay</option>
                            </select>
                        </div>
                        <div>
                            <label for="ap_diff" class="mb-1 block text-xs font-semibold">Filter Kesulitan (Opsional)</label>
                            <select id="ap_diff" v-model="autoPickForm.difficulty" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                                <option value="">Campuran (Semua Kesulitan)</option>
                                <option value="easy">Easy</option>
                                <option value="medium">Medium</option>
                                <option value="hard">Hard</option>
                            </select>
                        </div>
                    </div>
                    <div class="rounded-[8px] bg-[#F8FAFC] px-3 py-2 text-xs text-[#475569] dark:bg-[#162030] dark:text-[#CBD5E1]">
                        Stok soal yang cocok &amp; belum terpasang: <strong>{{ autoPickMatchingCount }}</strong> soal.
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showBankModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                        <button type="submit" :disabled="autoPickForm.processing || autoPickMatchingCount === 0" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                            {{ autoPickForm.processing ? 'Memproses...' : 'Generate Soal Acak' }}
                        </button>
                    </div>
                </form>

                <!-- TAB 3: CLONE FROM EXISTING QUIZ -->
                <form v-else @submit.prevent="submitCloneQuiz" class="space-y-4">
                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">
                        Salin seluruh soal dari kuis atau ujian lain milik Anda (cocok untuk kelas paralel atau ujian susulan).
                    </p>
                    <p v-if="otherQuizzes.length === 0" class="rounded-[10px] border border-dashed border-[#CBD5E1] p-5 text-center text-xs text-[#475569] dark:border-[#3E4F69] dark:text-[#CBD5E1]">
                        Belum ada kuis/ujian lain yang memiliki soal untuk disalin.
                    </p>
                    <div v-else>
                        <label for="clone_src" class="mb-1 block text-xs font-semibold">Pilih Kuis / Ujian Sumber</label>
                        <select id="clone_src" v-model="cloneForm.source_quiz_id" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                            <option v-for="oq in otherQuizzes" :key="oq.id" :value="oq.id">
                                [{{ oq.course?.code }}] {{ oq.title }} ({{ oq.questions_count ?? 0 }} soal)
                            </option>
                        </select>
                        <InputError :message="cloneForm.errors.source_quiz_id" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showBankModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                        <button type="submit" :disabled="cloneForm.processing || otherQuizzes.length === 0" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                            {{ cloneForm.processing ? 'Menyalin...' : 'Salin Semua Soal' }}
                        </button>
                    </div>
                </form>
            </div>
        </AppModal>

        <!-- MODAL: INLINE QUESTION CREATE / EDIT -->
        <AppModal :show="showInlineModal" :title="editingQuizQuestion ? 'Edit Soal' : 'Buat Soal Baru di Kuis Ini'" @close="showInlineModal = false">
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
                    <button type="button" @click="showInlineModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button
                        v-if="!editingQuizQuestion"
                        type="button"
                        :disabled="inlineForm.processing"
                        @click="submitInlineQuestion(true)"
                        class="min-h-[40px] rounded-[8px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] disabled:opacity-50 dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                    >
                        Simpan &amp; Tambah Soal Lagi
                    </button>
                    <button type="submit" :disabled="inlineForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        {{ editingQuizQuestion ? 'Simpan Perubahan' : 'Simpan & Tutup' }}
                    </button>
                </div>
            </form>
        </AppModal>

        <!-- MODAL: STUDENT PREVIEW -->
        <AppModal :show="showPreviewModal" :title="`Pratinjau Tampilan Siswa: ${quiz.title}`" max-width="xl" @close="showPreviewModal = false">
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

                <!-- Question number pills -->
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="(q, idx) in quiz.questions"
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

                <!-- Active Question -->
                <div class="rounded-[12px] border border-[#E2E8F0] p-4 dark:border-[#3E4F69]">
                    <div class="flex items-center justify-between text-xs text-[#475569] dark:text-[#CBD5E1]">
                        <span>Soal {{ previewIndex + 1 }} dari {{ quiz.questions?.length ?? 0 }}</span>
                        <span>{{ pointDrafts[previewQuestion.id] ?? previewQuestion.points }} poin</span>
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
                            <span class="h-3.5 w-3.5 rounded-full border border-current shrink-0"></span>
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
                        :disabled="previewIndex >= (quiz.questions?.length ?? 1) - 1"
                        @click="previewIndex++"
                        class="min-h-[38px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white disabled:opacity-40 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        Berikutnya
                    </button>
                </div>
            </div>
        </AppModal>

        <QuestionExcelImportModal
            :show="showImportModal"
            title="Import Soal Excel ke Kuis Ini"
            :post-url="route('instructor.quizzes.import-questions', [course.id, quiz.id])"
            :context-banner="`Soal dari Excel akan langsung masuk ke &quot;${quiz.title}&quot;.`"
            show-save-to-bank-toggle
            @close="showImportModal = false"
        />

        <!-- MODAL: ESSAY GRADING -->
        <AppModal :show="Boolean(gradingAttempt)" :title="`Nilai Jawaban Essay (${gradingAttempt?.student?.name ?? ''})`" @close="gradingAttempt = null">
            <form @submit.prevent="submitEssayGrades" class="space-y-4">
                <div v-if="essayError" role="alert" class="rounded-[8px] border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                    {{ essayError }}
                </div>
                <div v-for="row in essayRows" :key="row.answer_id" class="space-y-2 rounded-[10px] border border-[#E2E8F0] p-3.5 dark:border-[#3E4F69]">
                    <p class="text-xs font-bold">{{ row.question_text }} (Maks {{ row.max_points }} poin)</p>
                    <div class="rounded-[8px] bg-[#F8FAFC] p-2.5 text-xs whitespace-pre-line dark:bg-[#162030]">
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
                    <button type="button" @click="gradingAttempt = null" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="essaySubmitting" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        Simpan Nilai Essay
                    </button>
                </div>
            </form>
        </AppModal>

        <!-- MODAL: QUIZ SETTINGS -->
        <AppModal :show="showSettingsModal" title="Pengaturan Kuis / Ujian" @close="showSettingsModal = false">
            <form @submit.prevent="submitSettings" class="space-y-4">
                <div>
                    <label for="qs_title" class="mb-1 block text-xs font-semibold">Judul</label>
                    <input id="qs_title" v-model="settingsForm.title" type="text" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    <InputError :message="settingsForm.errors.title" />
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label for="qs_dur" class="mb-1 block text-xs font-semibold">Durasi (menit)</label>
                        <input id="qs_dur" v-model.number="settingsForm.duration_minutes" type="number" min="1" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="settingsForm.errors.duration_minutes" />
                    </div>
                    <div>
                        <label for="qs_att" class="mb-1 block text-xs font-semibold">Maks Percobaan</label>
                        <input id="qs_att" v-model.number="settingsForm.max_attempts" type="number" min="1" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="settingsForm.errors.max_attempts" />
                    </div>
                    <div>
                        <label for="qs_pass" class="mb-1 block text-xs font-semibold">KKM</label>
                        <input id="qs_pass" v-model.number="settingsForm.passing_score" type="number" min="0" max="100" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="settingsForm.errors.passing_score" />
                    </div>
                </div>
                <div class="grid gap-2 sm:grid-cols-2 text-xs">
                    <label class="flex items-center gap-2"><input v-model="settingsForm.shuffle_questions" type="checkbox" class="h-4 w-4 rounded" /> Acak soal</label>
                    <label class="flex items-center gap-2"><input v-model="settingsForm.shuffle_options" type="checkbox" class="h-4 w-4 rounded" /> Acak pilihan</label>
                    <label class="flex items-center gap-2"><input v-model="settingsForm.show_results" type="checkbox" class="h-4 w-4 rounded" /> Tampilkan hasil</label>
                    <label class="flex items-center gap-2"><input v-model="settingsForm.show_explanations" type="checkbox" class="h-4 w-4 rounded" /> Tampilkan pembahasan</label>
                    <label class="flex items-center gap-2"><input v-model="settingsForm.is_published" type="checkbox" class="h-4 w-4 rounded" /> Publikasikan ke siswa</label>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showSettingsModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="settingsForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Simpan</button>
                </div>
            </form>
        </AppModal>

        <ConfirmModal
            :show="Boolean(detachingQuestionId)"
            title="Lepaskan Soal"
            message="Lepaskan soal ini dari kuis/ujian? Soal tetap tersimpan di Bank Soal jika berasal dari bank."
            confirm-text="Lepaskan"
            :processing="isDetaching"
            @confirm="confirmDetachQuestion"
            @cancel="detachingQuestionId = null"
        />
    </AuthenticatedLayout>
</template>
