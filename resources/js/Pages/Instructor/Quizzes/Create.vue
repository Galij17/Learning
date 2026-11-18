<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuizWizardSettingsStep from '@/Components/Instructor/QuizWizard/QuizWizardSettingsStep.vue';
import QuizWizardQuestionsStep, { type NewQuestionDraft } from '@/Components/Instructor/QuizWizard/QuizWizardQuestionsStep.vue';
import QuizWizardReviewStep from '@/Components/Instructor/QuizWizard/QuizWizardReviewStep.vue';
import type { Question } from '@/types';

const props = defineProps<{
    course: { id: number; title: string; code: string; category_id: number; subject_id: number | null };
    lessons: Array<{ id: number; title: string; meeting_number: number }>;
    bankQuestions: Question[];
    subjects: Array<{ id: number; name: string; code: string; category_id: number }>;
}>();

const step = ref<1 | 2 | 3>(1);

const form = useForm({
    lesson_id: '' as number | string,
    title: '',
    description: '',
    type: 'quiz' as 'quiz' | 'exam',
    duration_minutes: 30,
    starts_at: '',
    ends_at: '',
    max_attempts: 2,
    passing_score: 70,
    shuffle_questions: true,
    shuffle_options: true,
    show_results: true,
    show_explanations: true,
    is_published: false,
    question_ids: [] as number[],
    new_questions: [] as NewQuestionDraft[],
});

const selectedBankObjects = computed(() => props.bankQuestions.filter((q) => form.question_ids.includes(q.id)));
const totalSelectedCount = computed(() => selectedBankObjects.value.length + form.new_questions.length);

const totalSelectedPoints = computed(() => {
    const bankPts = selectedBankObjects.value.reduce((s, q) => s + Number(q.points), 0);
    const newPts = form.new_questions.reduce((s, q) => s + Number(q.points), 0);
    return bankPts + newPts;
});

function nextStep(): void {
    if (step.value === 1) {
        if (!form.title.trim()) return;
        step.value = 2;
    } else if (step.value === 2) {
        step.value = 3;
    }
}

function submitQuiz(publishNow: boolean): void {
    form.is_published = publishNow;
    form.post(route('instructor.quizzes.store', props.course.id), {
        onError: () => {
            step.value = 1;
        },
    });
}
</script>

<template>
    <Head :title="`Buat Kuis / Ujian - ${course.title}`" />

    <AuthenticatedLayout :title="`Buat Kuis / Ujian (${course.code})`">
        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <Link
                    :href="route('instructor.courses.show', course.id)"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                >
                    <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                    Kembali ke {{ course.title }}
                </Link>
            </div>

            <!-- 3-Step Stepper -->
            <nav aria-label="Tahapan pembuatan kuis" class="grid gap-3 rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548] sm:grid-cols-3">
                <button
                    type="button"
                    @click="step = 1"
                    :class="[
                        'flex items-center gap-3 rounded-[10px] border p-3 text-left transition-colors',
                        step === 1
                            ? 'border-[#1B2CC1] bg-[#EFF6FF] dark:border-[#ABD2FA] dark:bg-[#162030]'
                            : 'border-[#E2E8F0] dark:border-[#3E4F69]',
                    ]"
                >
                    <span
                        :class="[
                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            step >= 1 ? 'bg-[#1B2CC1] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]' : 'bg-[#E2E8F0]',
                        ]"
                    >
                        <Check v-if="step > 1" class="h-4 w-4" aria-hidden="true" />
                        <span v-else>1</span>
                    </span>
                    <div>
                        <p class="text-xs font-bold">Pengaturan Dasar</p>
                        <p class="text-[11px] text-[#475569] dark:text-[#CBD5E1]">Judul, durasi, &amp; KKM</p>
                    </div>
                </button>

                <button
                    type="button"
                    @click="form.title.trim() ? (step = 2) : null"
                    :class="[
                        'flex items-center gap-3 rounded-[10px] border p-3 text-left transition-colors',
                        step === 2
                            ? 'border-[#1B2CC1] bg-[#EFF6FF] dark:border-[#ABD2FA] dark:bg-[#162030]'
                            : 'border-[#E2E8F0] dark:border-[#3E4F69]',
                    ]"
                >
                    <span
                        :class="[
                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            step >= 2 ? 'bg-[#1B2CC1] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]' : 'bg-[#E2E8F0] text-[#475569] dark:bg-[#3E4F69] dark:text-[#CBD5E1]',
                        ]"
                    >
                        <Check v-if="step > 2" class="h-4 w-4" aria-hidden="true" />
                        <span v-else>2</span>
                    </span>
                    <div>
                        <p class="text-xs font-bold">Pilih &amp; Buat Soal</p>
                        <p class="text-[11px] text-[#475569] dark:text-[#CBD5E1]">{{ totalSelectedCount }} soal dipilih</p>
                    </div>
                </button>

                <button
                    type="button"
                    @click="form.title.trim() ? (step = 3) : null"
                    :class="[
                        'flex items-center gap-3 rounded-[10px] border p-3 text-left transition-colors',
                        step === 3
                            ? 'border-[#1B2CC1] bg-[#EFF6FF] dark:border-[#ABD2FA] dark:bg-[#162030]'
                            : 'border-[#E2E8F0] dark:border-[#3E4F69]',
                    ]"
                >
                    <span
                        :class="[
                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            step === 3 ? 'bg-[#1B2CC1] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]' : 'bg-[#E2E8F0] text-[#475569] dark:bg-[#3E4F69] dark:text-[#CBD5E1]',
                        ]"
                    >
                        3
                    </span>
                    <div>
                        <p class="text-xs font-bold">Review &amp; Terbitkan</p>
                        <p class="text-[11px] text-[#475569] dark:text-[#CBD5E1]">Draft atau publikasikan</p>
                    </div>
                </button>
            </nav>

            <QuizWizardSettingsStep
                v-show="step === 1"
                :form="form"
                :lessons="lessons"
                @next="nextStep"
            />

            <QuizWizardQuestionsStep
                v-show="step === 2"
                :bank-questions="bankQuestions"
                :subjects="subjects"
                :course-subject-id="course.subject_id"
                :question-ids="form.question_ids"
                :new-questions="form.new_questions"
                :total-selected-count="totalSelectedCount"
                :total-selected-points="totalSelectedPoints"
                @update:question-ids="form.question_ids = $event"
                @add-new-question="form.new_questions.push($event)"
                @back="step = 1"
                @next="nextStep"
            />

            <QuizWizardReviewStep
                v-show="step === 3"
                :title="form.title"
                :type="form.type"
                :duration-minutes="form.duration_minutes"
                :max-attempts="form.max_attempts"
                :passing-score="form.passing_score"
                :selected-bank-objects="selectedBankObjects"
                :new-questions="form.new_questions"
                :total-selected-count="totalSelectedCount"
                :total-selected-points="totalSelectedPoints"
                :processing="form.processing"
                @remove-bank-question="form.question_ids = form.question_ids.filter((id) => id !== $event)"
                @remove-new-question="form.new_questions.splice($event, 1)"
                @back="step = 2"
                @submit="submitQuiz"
            />
        </div>
    </AuthenticatedLayout>
</template>
