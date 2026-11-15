<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, Clock, Save, Send } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import { useToast } from '@/composables/useToast';

interface TakeQuestion {
    id: number;
    type: 'multiple_choice' | 'true_false' | 'essay';
    question_text: string;
    points: number;
    options: Array<{ id: number; option_text: string }>;
}

interface SavedAnswer {
    question_id: number;
    selected_option_id: number | null;
    essay_answer: string | null;
}

const props = defineProps<{
    course: { id: number; title: string; code: string };
    quiz: { id: number; title: string; type: string; duration_minutes: number };
    attempt: { id: number; attempt_number: number; expires_at: string; remaining_seconds: number };
    questions: TakeQuestion[];
    savedAnswers: Record<number, SavedAnswer>;
}>();

const toast = useToast();
const currentIndex = ref(0);
const remainingSeconds = ref(Math.max(0, Math.floor(props.attempt.remaining_seconds)));
const saving = ref(false);
const lastSavedAt = ref<string | null>(null);
const submitting = ref(false);
const showConfirmSubmit = ref(false);

const answers = ref<Record<number, { selected_option_id: number | null; essay_answer: string }>>({});

for (const q of props.questions) {
    const existing = props.savedAnswers?.[q.id];
    answers.value[q.id] = {
        selected_option_id: existing?.selected_option_id ?? null,
        essay_answer: existing?.essay_answer ?? '',
    };
}

const currentQuestion = computed(() => props.questions[currentIndex.value] ?? null);

const formattedTime = computed(() => {
    const hrs = Math.floor(remainingSeconds.value / 3600);
    const mins = Math.floor((remainingSeconds.value % 3600) / 60);
    const secs = remainingSeconds.value % 60;
    const pad = (n: number) => String(n).padStart(2, '0');
    return hrs > 0 ? `${pad(hrs)}:${pad(mins)}:${pad(secs)}` : `${pad(mins)}:${pad(secs)}`;
});

const answeredCount = computed(
    () =>
        props.questions.filter((q) => {
            const ans = answers.value[q.id];
            return q.type === 'essay' ? Boolean(ans?.essay_answer?.trim()) : ans?.selected_option_id !== null;
        }).length
);

let countdownTimer: ReturnType<typeof setInterval> | null = null;
let periodicSaveTimer: ReturnType<typeof setInterval> | null = null;
let essayDebounce: ReturnType<typeof setTimeout> | null = null;

async function persistAnswer(questionId: number): Promise<void> {
    const ans = answers.value[questionId];
    if (!ans) return;
    saving.value = true;
    try {
        const res = await window.axios.post(route('student.quizzes.autosave', [props.course.id, props.quiz.id, props.attempt.id]), {
            question_id: questionId,
            selected_option_id: ans.selected_option_id,
            essay_answer: ans.essay_answer || null,
        });
        lastSavedAt.value = res.data.saved_at ?? null;
        if (typeof res.data.remaining_seconds === 'number') {
            remainingSeconds.value = Math.max(0, Math.floor(res.data.remaining_seconds));
        }
    } catch {
        toast.error('Gagal autosave jawaban. Periksa koneksi Anda.');
    } finally {
        saving.value = false;
    }
}

function selectOption(questionId: number, optionId: number): void {
    answers.value[questionId].selected_option_id = optionId;
    void persistAnswer(questionId);
}

function onEssayInput(questionId: number): void {
    if (essayDebounce) clearTimeout(essayDebounce);
    essayDebounce = setTimeout(() => {
        void persistAnswer(questionId);
    }, 800);
}

async function submitExam(auto = false): Promise<void> {
    if (submitting.value) return;
    submitting.value = true;
    showConfirmSubmit.value = false;

    if (currentQuestion.value) {
        await persistAnswer(currentQuestion.value.id);
    }

    router.post(
        route('student.quizzes.submit', [props.course.id, props.quiz.id, props.attempt.id]),
        { auto_submitted: auto },
        {
            onFinish: () => {
                submitting.value = false;
            },
        }
    );
}

onMounted(() => {
    countdownTimer = setInterval(() => {
        if (remainingSeconds.value > 0) {
            remainingSeconds.value -= 1;
        }
        if (remainingSeconds.value <= 0) {
            if (countdownTimer) clearInterval(countdownTimer);
            void submitExam(true);
        }
    }, 1000);

    periodicSaveTimer = setInterval(() => {
        if (currentQuestion.value) {
            void persistAnswer(currentQuestion.value.id);
        }
    }, 15000);
});

onBeforeUnmount(() => {
    if (countdownTimer) clearInterval(countdownTimer);
    if (periodicSaveTimer) clearInterval(periodicSaveTimer);
    if (essayDebounce) clearTimeout(essayDebounce);
});
</script>

<template>
    <Head :title="`Mengerjakan: ${quiz.title}`" />

    <AuthenticatedLayout :title="quiz.title">
        <div class="space-y-5">
            <!-- Top Timer & Status Bar -->
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div>
                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ course.title }} • Percobaan #{{ attempt.attempt_number }}</p>
                    <h2 class="text-base font-bold">{{ quiz.title }}</h2>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="flex items-center gap-1.5 text-xs text-[#475569] dark:text-[#CBD5E1]">
                        <Save class="h-3.5 w-3.5" aria-hidden="true" />
                        <span v-if="saving">Menyimpan...</span>
                        <span v-else-if="lastSavedAt">Tersimpan {{ lastSavedAt }}</span>
                        <span v-else>Autosave aktif</span>
                    </span>

                    <div
                        :class="[
                            'flex items-center gap-2 rounded-[10px] px-3.5 py-2 font-mono text-sm font-extrabold',
                            remainingSeconds <= 120
                                ? 'bg-[#FEE2E2] text-[#DC2626]'
                                : 'bg-[#F8FAFC] text-[#1E293B] dark:bg-[#3E4F69] dark:text-[#ABD2FA]',
                        ]"
                    >
                        <Clock class="h-4 w-4" aria-hidden="true" />
                        <span>{{ formattedTime }}</span>
                    </div>

                    <button
                        type="button"
                        @click="showConfirmSubmit = true"
                        :disabled="submitting"
                        class="inline-flex min-h-[42px] items-center gap-2 rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <Send class="h-4 w-4" aria-hidden="true" />
                        Selesai & Kirim
                    </button>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-4">
                <!-- Question Canvas -->
                <section v-if="currentQuestion" class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548] lg:col-span-3">
                    <div class="flex items-center justify-between border-b border-[#E2E8F0] pb-3 dark:border-[#3E4F69]">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#1B2CC1] dark:text-[#ABD2FA]">
                            Soal {{ currentIndex + 1 }} dari {{ questions.length }}
                        </span>
                        <span class="text-xs text-[#475569] dark:text-[#CBD5E1]">Bobot: {{ currentQuestion.points }} poin</span>
                    </div>

                    <p class="mt-4 whitespace-pre-line text-base font-medium leading-relaxed">{{ currentQuestion.question_text }}</p>

                    <div v-if="currentQuestion.type !== 'essay'" class="mt-6 space-y-3">
                        <button
                            v-for="opt in currentQuestion.options"
                            :key="opt.id"
                            type="button"
                            @click="selectOption(currentQuestion.id, opt.id)"
                            :class="[
                                'flex w-full items-center gap-3 rounded-[10px] border p-4 text-left text-sm transition-colors',
                                answers[currentQuestion.id]?.selected_option_id === opt.id
                                    ? 'border-[#1B2CC1] bg-[#F8FAFC] font-semibold text-[#1E293B] dark:border-[#ABD2FA] dark:bg-[#3E4F69] dark:text-[#ABD2FA]'
                                    : 'border-[#E2E8F0] hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:hover:bg-[#162030]',
                            ]"
                        >
                            <CheckCircle2
                                v-if="answers[currentQuestion.id]?.selected_option_id === opt.id"
                                class="h-5 w-5 shrink-0 text-[#1B2CC1] dark:text-[#ABD2FA]"
                                aria-hidden="true"
                            />
                            <span
                                v-else
                                class="h-5 w-5 shrink-0 rounded-full border-2 border-[#475569]/40"
                                aria-hidden="true"
                            />
                            <span>{{ opt.option_text }}</span>
                        </button>
                    </div>

                    <div v-else class="mt-6">
                        <label :for="`essay_${currentQuestion.id}`" class="mb-2 block text-xs font-semibold">Jawaban Essay Anda</label>
                        <textarea
                            :id="`essay_${currentQuestion.id}`"
                            v-model="answers[currentQuestion.id].essay_answer"
                            @input="onEssayInput(currentQuestion.id)"
                            rows="7"
                            placeholder="Tuliskan jawaban essay secara lengkap..."
                            class="w-full rounded-[10px] border border-[#E2E8F0] p-3.5 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                        ></textarea>
                    </div>

                    <div class="mt-8 flex items-center justify-between border-t border-[#E2E8F0] pt-4 dark:border-[#3E4F69]">
                        <button
                            type="button"
                            :disabled="currentIndex === 0"
                            @click="currentIndex--"
                            class="min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold disabled:opacity-40 dark:border-[#1B2CC1]"
                        >
                            Sebelumnya
                        </button>
                        <button
                            v-if="currentIndex < questions.length - 1"
                            type="button"
                            @click="currentIndex++"
                            class="min-h-[42px] rounded-[8px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            Soal Selanjutnya
                        </button>
                        <button
                            v-else
                            type="button"
                            @click="showConfirmSubmit = true"
                            class="min-h-[42px] rounded-[8px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            Kumpulkan Sekarang
                        </button>
                    </div>
                </section>

                <!-- Navigator Sidebar -->
                <aside class="rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <h3 class="text-sm font-bold">Navigasi Soal</h3>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Terjawab: {{ answeredCount }} / {{ questions.length }}</p>

                    <div class="mt-4 grid grid-cols-5 gap-2">
                        <button
                            v-for="(q, idx) in questions"
                            :key="q.id"
                            type="button"
                            @click="currentIndex = idx"
                            :class="[
                                'flex h-10 items-center justify-center rounded-[8px] text-xs font-bold transition-colors',
                                idx === currentIndex
                                    ? 'ring-2 ring-[#7692FF] bg-[#1E293B] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]'
                                    : (q.type === 'essay' ? Boolean(answers[q.id]?.essay_answer?.trim()) : answers[q.id]?.selected_option_id !== null)
                                      ? 'bg-[#1B2CC1] text-white dark:bg-[#7692FF]'
                                      : 'border border-[#E2E8F0] bg-[#F8FAFC] text-[#475569] dark:border-[#3E4F69] dark:bg-[#162030] dark:text-[#CBD5E1]',
                            ]"
                        >
                            {{ idx + 1 }}
                        </button>
                    </div>
                </aside>
            </div>
        </div>

        <AppModal :show="showConfirmSubmit" title="Konfirmasi Pengumpulan" @close="showConfirmSubmit = false">
            <div class="space-y-4">
                <p class="text-sm">
                    Anda telah menjawab <strong>{{ answeredCount }}</strong> dari <strong>{{ questions.length }}</strong> soal. Jawaban yang telah dikumpulkan tidak dapat diubah kembali.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showConfirmSubmit = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Kembali Mengerjakan</button>
                    <button type="button" @click="submitExam(false)" :disabled="submitting" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        Ya, Kumpulkan Sekarang
                    </button>
                </div>
            </div>
        </AppModal>
    </AuthenticatedLayout>
</template>
