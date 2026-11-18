<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, BookMarked, Eye, FileSpreadsheet, Plus, Settings } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import QuestionExcelImportModal from '@/Components/QuestionExcelImportModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import QuizAttemptsTable from '@/Components/Instructor/Quiz/QuizAttemptsTable.vue';
import QuizBankPickerModal from '@/Components/Instructor/Quiz/QuizBankPickerModal.vue';
import QuizEssayGradingModal from '@/Components/Instructor/Quiz/QuizEssayGradingModal.vue';
import QuizInlineQuestionModal from '@/Components/Instructor/Quiz/QuizInlineQuestionModal.vue';
import QuizQuestionsSection from '@/Components/Instructor/Quiz/QuizQuestionsSection.vue';
import QuizSettingsModal from '@/Components/Instructor/Quiz/QuizSettingsModal.vue';
import QuizStudentPreviewModal from '@/Components/Instructor/Quiz/QuizStudentPreviewModal.vue';
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
const bankInitialTab = ref<'manual' | 'autopick' | 'clone'>('manual');
const showImportModal = ref(false);
const showInlineModal = ref(false);
const showSettingsModal = ref(false);
const showPreviewModal = ref(false);
const editingQuizQuestion = ref<Question | null>(null);
const gradingAttempt = ref<QuizAttempt | null>(null);
const detachingQuestionId = ref<number | null>(null);
const isDetaching = ref(false);

const totalQuizPoints = computed(() =>
    (props.quiz.questions ?? []).reduce(
        (sum, q) => sum + Number((q as Question & { pivot?: { points?: number | string } }).pivot?.points ?? q.points),
        0
    )
);

function openBankModal(tab: 'manual' | 'autopick' | 'clone' = 'manual'): void {
    bankInitialTab.value = tab;
    showBankModal.value = true;
}

function openInlineModal(): void {
    editingQuizQuestion.value = null;
    showInlineModal.value = true;
}

function openInlineEdit(q: Question): void {
    editingQuizQuestion.value = q;
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

function saveQuestionPoints(questionId: number, points: number): void {
    router.patch(
        route('instructor.quizzes.question-points', [props.course.id, props.quiz.id, questionId]),
        { points },
        { preserveScroll: true }
    );
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
</script>

<template>
    <Head :title="quiz.title" />

    <AuthenticatedLayout :title="quiz.title">
        <div class="space-y-6">
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
                            @click="showPreviewModal = true"
                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#E2E8F0] px-3.5 text-xs font-semibold hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:hover:bg-[#162030]"
                        >
                            <Eye class="h-4 w-4" aria-hidden="true" />
                            Pratinjau Siswa
                        </button>
                        <button type="button" @click="showSettingsModal = true" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
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

            <QuizQuestionsSection
                :questions="quiz.questions ?? []"
                :total-quiz-points="totalQuizPoints"
                @save-points="saveQuestionPoints"
                @move-question="moveQuestion"
                @edit-question="openInlineEdit"
                @detach-question="detachingQuestionId = $event"
            />

            <QuizAttemptsTable
                :attempts="attempts"
                @grade-essay="gradingAttempt = $event"
            />
        </div>

        <QuizBankPickerModal
            :show="showBankModal"
            :initial-tab="bankInitialTab"
            :course-id="course.id"
            :course-subject-id="course.subject_id"
            :quiz-id="quiz.id"
            :bank-questions="bankQuestions"
            :subjects="subjects"
            :other-quizzes="otherQuizzes"
            @close="showBankModal = false"
        />

        <QuizInlineQuestionModal
            :show="showInlineModal"
            :course-id="course.id"
            :quiz-id="quiz.id"
            :editing-question="editingQuizQuestion"
            @close="showInlineModal = false; editingQuizQuestion = null"
        />

        <QuizStudentPreviewModal
            :show="showPreviewModal"
            :quiz="quiz"
            @close="showPreviewModal = false"
        />

        <QuestionExcelImportModal
            :show="showImportModal"
            title="Import Soal Excel ke Kuis Ini"
            :post-url="route('instructor.quizzes.import-questions', [course.id, quiz.id])"
            :context-banner="`Soal dari Excel akan langsung masuk ke &quot;${quiz.title}&quot;.`"
            show-save-to-bank-toggle
            @close="showImportModal = false"
        />

        <QuizEssayGradingModal
            :attempt="gradingAttempt"
            :course-id="course.id"
            :quiz-id="quiz.id"
            @close="gradingAttempt = null"
        />

        <QuizSettingsModal
            :show="showSettingsModal"
            :course-id="course.id"
            :quiz="quiz"
            @close="showSettingsModal = false"
        />

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
