<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, BookMarked, Check, CheckCircle2, Plus, Send, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Question } from '@/types';

interface NewQuestionDraft {
    type: 'multiple_choice' | 'true_false' | 'essay';
    question_text: string;
    explanation: string;
    points: number;
    difficulty: 'easy' | 'medium' | 'hard';
    options: Array<{ option_text: string; is_correct: boolean }>;
}

const props = defineProps<{
    course: { id: number; title: string; code: string; category_id: number; subject_id: number | null };
    lessons: Array<{ id: number; title: string; meeting_number: number }>;
    bankQuestions: Question[];
    subjects: Array<{ id: number; name: string; code: string; category_id: number }>;
}>();

const step = ref<1 | 2 | 3>(1);
const step2Tab = ref<'bank' | 'new'>('bank');

const bankSearch = ref('');
const bankSubjectFilter = ref(
    props.course.subject_id && props.bankQuestions.some((q) => q.subject_id === props.course.subject_id)
        ? String(props.course.subject_id)
        : ''
);
const bankTypeFilter = ref('');

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

const selectedBankObjects = computed(() => props.bankQuestions.filter((q) => form.question_ids.includes(q.id)));

const totalSelectedCount = computed(() => selectedBankObjects.value.length + form.new_questions.length);

const totalSelectedPoints = computed(() => {
    const bankPts = selectedBankObjects.value.reduce((s, q) => s + Number(q.points), 0);
    const newPts = form.new_questions.reduce((s, q) => s + Number(q.points), 0);
    return bankPts + newPts;
});

const allFilteredSelected = computed(
    () => filteredBank.value.length > 0 && filteredBank.value.every((q) => form.question_ids.includes(q.id))
);

function toggleSelectAllBank(): void {
    const ids = filteredBank.value.map((q) => q.id);
    if (allFilteredSelected.value) {
        const idSet = new Set(ids);
        form.question_ids = form.question_ids.filter((id) => !idSet.has(id));
    } else {
        form.question_ids = Array.from(new Set([...form.question_ids, ...ids]));
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
    form.new_questions.push({
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

function removeNewQuestion(idx: number): void {
    form.new_questions.splice(idx, 1);
}

function removeBankQuestion(id: number): void {
    form.question_ids = form.question_ids.filter((qId) => qId !== id);
}

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

            <!-- STEP 1: SETTINGS -->
            <section v-show="step === 1" class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
                <form @submit.prevent="nextStep" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="w_title" class="mb-1 block text-xs font-semibold">Judul Kuis / Ujian</label>
                            <input id="w_title" v-model="form.title" type="text" required placeholder="Contoh: Kuis Bab 1 - Algoritma" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                            <InputError :message="form.errors.title" />
                        </div>
                        <div class="grid gap-3 grid-cols-2">
                            <div>
                                <label for="w_type" class="mb-1 block text-xs font-semibold">Jenis</label>
                                <select id="w_type" v-model="form.type" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                                    <option value="quiz">Kuis</option>
                                    <option value="exam">Ujian</option>
                                </select>
                            </div>
                            <div>
                                <label for="w_lesson" class="mb-1 block text-xs font-semibold">Bab (Opsional)</label>
                                <select id="w_lesson" v-model="form.lesson_id" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                                    <option value="">Umum</option>
                                    <option v-for="l in lessons" :key="l.id" :value="l.id">P{{ l.meeting_number }}: {{ l.title }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="w_desc" class="mb-1 block text-xs font-semibold">Petunjuk Pengerjaan (Opsional)</label>
                        <textarea id="w_desc" v-model="form.description" rows="2" placeholder="Instruksi untuk siswa..." class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="w_dur" class="mb-1 block text-xs font-semibold">Durasi (menit)</label>
                            <input id="w_dur" v-model.number="form.duration_minutes" type="number" min="1" max="360" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                            <InputError :message="form.errors.duration_minutes" />
                        </div>
                        <div>
                            <label for="w_att" class="mb-1 block text-xs font-semibold">Maks Percobaan</label>
                            <input id="w_att" v-model.number="form.max_attempts" type="number" min="1" max="20" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                            <InputError :message="form.errors.max_attempts" />
                        </div>
                        <div>
                            <label for="w_pass" class="mb-1 block text-xs font-semibold">Nilai KKM (%)</label>
                            <input id="w_pass" v-model.number="form.passing_score" type="number" min="0" max="100" required class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                            <InputError :message="form.errors.passing_score" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="w_start" class="mb-1 block text-xs font-semibold">Mulai (Opsional)</label>
                            <input id="w_start" v-model="form.starts_at" type="datetime-local" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                            <InputError :message="form.errors.starts_at" />
                        </div>
                        <div>
                            <label for="w_end" class="mb-1 block text-xs font-semibold">Selesai (Opsional)</label>
                            <input id="w_end" v-model="form.ends_at" type="datetime-local" class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                            <InputError :message="form.errors.ends_at" />
                        </div>
                    </div>

                    <div class="grid gap-2 pt-1 text-xs sm:grid-cols-2">
                        <label class="flex items-center gap-2"><input v-model="form.shuffle_questions" type="checkbox" class="h-4 w-4 rounded" /> Acak urutan soal</label>
                        <label class="flex items-center gap-2"><input v-model="form.shuffle_options" type="checkbox" class="h-4 w-4 rounded" /> Acak pilihan jawaban</label>
                        <label class="flex items-center gap-2"><input v-model="form.show_results" type="checkbox" class="h-4 w-4 rounded" /> Tampilkan hasil ke siswa</label>
                        <label class="flex items-center gap-2"><input v-model="form.show_explanations" type="checkbox" class="h-4 w-4 rounded" /> Tampilkan pembahasan</label>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                            Lanjut ke Pilih Soal
                            <ArrowRight class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>
                </form>
            </section>

            <!-- STEP 2: PICK OR ADD QUESTIONS -->
            <section v-show="step === 2" class="space-y-5 rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
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
                            Buat Soal Baru ({{ form.new_questions.length }})
                        </button>
                    </div>
                </div>

                <!-- Tab Bank -->
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
                            <input v-model="form.question_ids" type="checkbox" :value="bq.id" class="mt-1 h-4 w-4 rounded" />
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

                <!-- Tab New Inline Questions -->
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
                    <button type="button" @click="step = 1" class="inline-flex min-h-[42px] items-center gap-1.5 rounded-[10px] border border-[#E2E8F0] px-4 text-xs font-semibold dark:border-[#3E4F69]">
                        <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                        Kembali
                    </button>
                    <button type="button" @click="nextStep" class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        Lanjut ke Review
                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </section>

            <!-- STEP 3: REVIEW & PUBLISH -->
            <section v-show="step === 3" class="space-y-5 rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="rounded-[12px] border border-[#E2E8F0] bg-[#F8FAFC] p-4 dark:border-[#3E4F69] dark:bg-[#162030]">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-base font-bold">{{ form.title || '(Belum ada judul)' }}</h3>
                            <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                                {{ form.type === 'exam' ? 'Ujian' : 'Kuis' }} • Durasi {{ form.duration_minutes }} menit • Maks {{ form.max_attempts }}x percobaan • KKM {{ form.passing_score }}%
                            </p>
                        </div>
                        <StatusBadge :label="`${totalSelectedCount} Soal • ${totalSelectedPoints} Poin`" variant="mint" />
                    </div>
                </div>

                <div v-if="totalSelectedCount === 0" class="rounded-[10px] border border-amber-300 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                    Belum ada soal dipilih. Anda tetap bisa menyimpan sebagai <strong>Draft</strong> lalu mengimpor Excel atau menambah soal di halaman berikutnya.
                </div>

                <div v-else class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    <div
                        v-for="(q, idx) in selectedBankObjects"
                        :key="`b-${q.id}`"
                        class="flex items-center justify-between gap-3 rounded-[10px] border border-[#E2E8F0] px-4 py-2.5 text-xs dark:border-[#3E4F69]"
                    >
                        <div class="min-w-0">
                            <span class="font-mono font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">#{{ idx + 1 }}</span>
                            <span class="ml-2 font-medium">{{ q.question_text }}</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <StatusBadge :label="`${q.points} poin`" variant="neutral" />
                            <button type="button" @click="removeBankQuestion(q.id)" aria-label="Hapus dari daftar" class="text-red-600 hover:underline">
                                <Trash2 class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <div
                        v-for="(nq, idx) in form.new_questions"
                        :key="`n-${idx}`"
                        class="flex items-center justify-between gap-3 rounded-[10px] border border-[#7692FF] px-4 py-2.5 text-xs dark:border-[#ABD2FA]"
                    >
                        <div class="min-w-0">
                            <span class="font-mono font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">#{{ selectedBankObjects.length + idx + 1 }}</span>
                            <StatusBadge label="Baru" variant="pine" class="ml-2" />
                            <span class="ml-2 font-medium">{{ nq.question_text }}</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <StatusBadge :label="`${nq.points} poin`" variant="neutral" />
                            <button type="button" @click="removeNewQuestion(idx)" aria-label="Hapus dari daftar" class="text-red-600 hover:underline">
                                <Trash2 class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <button type="button" @click="step = 2" class="inline-flex min-h-[42px] items-center gap-1.5 rounded-[10px] border border-[#E2E8F0] px-4 text-xs font-semibold dark:border-[#3E4F69]">
                        <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                        Kembali ke Soal
                    </button>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            :disabled="form.processing"
                            @click="submitQuiz(false)"
                            class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] disabled:opacity-50 dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                        >
                            Simpan sebagai Draft
                        </button>
                        <button
                            type="button"
                            :disabled="form.processing || totalSelectedCount === 0"
                            @click="submitQuiz(true)"
                            class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            <Send class="h-3.5 w-3.5" aria-hidden="true" />
                            {{ form.processing ? 'Menyimpan...' : 'Terbitkan ke Siswa' }}
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
