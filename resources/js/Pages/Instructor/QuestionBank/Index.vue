<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, BookMarked, FileSpreadsheet, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import QuestionExcelImportModal from '@/Components/QuestionExcelImportModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import QuestionBankCard from '@/Components/Instructor/QuestionBank/QuestionBankCard.vue';
import QuestionBulkActionBar from '@/Components/Instructor/QuestionBank/QuestionBulkActionBar.vue';
import QuestionFormModal from '@/Components/Instructor/QuestionBank/QuestionFormModal.vue';
import SubjectDirectoryView, { type SubjectWithCount } from '@/Components/Instructor/QuestionBank/SubjectDirectoryView.vue';
import type { PaginatedData, Question } from '@/types';

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

const totalBankQuestions = computed(
    () => props.subjects.reduce((sum, s) => sum + Number(s.questions_count ?? 0), 0) + Number(props.unassignedCount ?? 0)
);

const showModal = ref(false);
const showImportModal = ref(false);
const editingQuestion = ref<Question | null>(null);
const presetSubject = ref<SubjectWithCount | null>(null);
const deletingQuestionId = ref<number | null>(null);
const isDeletingQuestion = ref(false);

const selectedIds = ref<number[]>([]);
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
    selectedIds.value = allPageSelected.value ? [] : props.questions.data.map((q) => q.id);
}

function toggleSelectQuestion(id: number): void {
    selectedIds.value = selectedIds.value.includes(id)
        ? selectedIds.value.filter((item) => item !== id)
        : [...selectedIds.value, id];
}

function openSubject(id: number | 'unassigned'): void {
    router.get(route('instructor.question-bank.index'), { subject_id: id }, { preserveScroll: true });
}

function openAllQuestions(): void {
    router.get(route('instructor.question-bank.index'), { view: 'all' }, { preserveScroll: true });
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

function openCreate(subject: SubjectWithCount | null): void {
    editingQuestion.value = null;
    presetSubject.value = subject;
    showModal.value = true;
}

function openEdit(q: Question): void {
    editingQuestion.value = q;
    presetSubject.value = null;
    showModal.value = true;
}

function duplicateQuestion(id: number): void {
    router.post(route('instructor.question-bank.duplicate', id), {}, { preserveScroll: true });
}

function runBulkMoveSubject(targetSubjectId: number): void {
    if (selectedIds.value.length === 0) return;
    isBulkProcessing.value = true;
    router.post(
        route('instructor.question-bank.bulk-action'),
        { action: 'move_subject', question_ids: selectedIds.value, subject_id: targetSubjectId },
        {
            preserveScroll: true,
            onFinish: () => {
                isBulkProcessing.value = false;
                selectedIds.value = [];
            },
        }
    );
}

function runBulkAttachQuiz(targetQuizId: number): void {
    if (selectedIds.value.length === 0) return;
    isBulkProcessing.value = true;
    router.post(
        route('instructor.question-bank.bulk-action'),
        { action: 'attach_to_quiz', question_ids: selectedIds.value, quiz_id: targetQuizId },
        {
            preserveScroll: true,
            onFinish: () => {
                isBulkProcessing.value = false;
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
        { action: 'delete', question_ids: selectedIds.value },
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
</script>

<template>
    <Head title="Bank Soal" />

    <AuthenticatedLayout title="Bank Soal Pembelajaran">
        <SubjectDirectoryView
            v-if="isDirectoryMode"
            :subjects="subjects"
            :categories="categories"
            :unassigned-count="unassignedCount"
            :total-bank-questions="totalBankQuestions"
            @open-all="openAllQuestions"
            @open-import="showImportModal = true"
            @open-create="openCreate"
            @open-subject="openSubject"
        />

        <div v-else class="space-y-5">
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

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]">
                <form @submit.prevent="applyFilter" class="flex w-full flex-wrap items-center gap-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari teks pertanyaan..."
                        class="min-h-[42px] min-w-[200px] flex-1 rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
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
                <QuestionBulkActionBar
                    :selected-count="selectedIds.length"
                    :all-page-selected="allPageSelected"
                    :subjects="subjects"
                    :instructor-quizzes="instructorQuizzes"
                    :processing="isBulkProcessing"
                    @toggle-select-all="toggleSelectAllPage"
                    @move-subject="runBulkMoveSubject"
                    @attach-quiz="runBulkAttachQuiz"
                    @request-bulk-delete="showBulkDeleteConfirm = true"
                />

                <QuestionBankCard
                    v-for="q in questions.data"
                    :key="q.id"
                    :question="q"
                    :selected="selectedIds.includes(q.id)"
                    @toggle-select="toggleSelectQuestion"
                    @duplicate="duplicateQuestion"
                    @edit="openEdit"
                    @delete="deletingQuestionId = $event"
                />
            </div>

            <PaginationBar :links="questions.links" :from="questions.from" :to="questions.to" :total="questions.total" />
        </div>

        <QuestionFormModal
            :show="showModal"
            :editing-question="editingQuestion"
            :preset-subject="presetSubject"
            :selected-subject="selectedSubject"
            :categories="categories"
            :subjects="subjects"
            @close="showModal = false"
        />

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
