<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { BookMarked, Copy, Shuffle } from 'lucide-vue-next';
import AppModal from '@/Components/AppModal.vue';
import InputError from '@/Components/InputError.vue';
import type { Question } from '@/types';

const props = defineProps<{
    show: boolean;
    initialTab: 'manual' | 'autopick' | 'clone';
    courseId: number;
    courseSubjectId: number | null;
    quizId: number;
    bankQuestions: Question[];
    subjects: Array<{ id: number; name: string; code: string; category_id: number }>;
    otherQuizzes: Array<{
        id: number;
        course_id: number;
        title: string;
        type: string;
        questions_count?: number;
        course?: { id: number; title: string; code: string };
    }>;
}>();

const emit = defineEmits<{
    close: [];
}>();

const bankModalTab = ref<'manual' | 'autopick' | 'clone'>('manual');
const selectedBankIds = ref<number[]>([]);
const bankSearch = ref('');
const bankSubjectFilter = ref('');
const bankTypeFilter = ref('');
const bankDifficultyFilter = ref('');

const autoPickForm = useForm({
    count: 10,
    subject_id: props.courseSubjectId ?? ('' as number | string),
    type: '',
    difficulty: '',
});

const cloneForm = useForm({
    source_quiz_id: props.otherQuizzes[0]?.id ?? ('' as number | string),
});

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) return;
        bankModalTab.value = props.initialTab;
        bankSearch.value = '';
        bankTypeFilter.value = '';
        bankDifficultyFilter.value = '';

        const defaultSubj = props.courseSubjectId ? String(props.courseSubjectId) : '';
        const hasMatchesForCourseSubject =
            defaultSubj !== '' && props.bankQuestions.some((item) => String(item.subject_id ?? '') === defaultSubj);
        bankSubjectFilter.value = hasMatchesForCourseSubject ? defaultSubj : '';

        autoPickForm.clearErrors();
        autoPickForm.subject_id = hasMatchesForCourseSubject && props.courseSubjectId ? props.courseSubjectId : '';
        cloneForm.clearErrors();
        if (!cloneForm.source_quiz_id && props.otherQuizzes.length > 0) {
            cloneForm.source_quiz_id = props.otherQuizzes[0].id;
        }
    }
);

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

function toggleSelectAllFiltered(): void {
    const visibleIds = filteredBankQuestions.value.map((q) => q.id);
    if (allFilteredSelected.value) {
        const visibleSet = new Set(visibleIds);
        selectedBankIds.value = selectedBankIds.value.filter((id) => !visibleSet.has(id));
    } else {
        selectedBankIds.value = Array.from(new Set([...selectedBankIds.value, ...visibleIds]));
    }
}

function submitBankAttach(): void {
    if (selectedBankIds.value.length === 0) return;
    router.post(
        route('instructor.quizzes.attach-questions', [props.courseId, props.quizId]),
        { question_ids: selectedBankIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedBankIds.value = [];
                emit('close');
            },
        }
    );
}

function submitAutoPick(): void {
    autoPickForm.post(route('instructor.quizzes.auto-pick', [props.courseId, props.quizId]), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}

function submitCloneQuiz(): void {
    if (!cloneForm.source_quiz_id) return;
    cloneForm.post(route('instructor.quizzes.clone-questions', [props.courseId, props.quizId]), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <AppModal :show="show" title="Tambahkan Soal dari Bank / Kuis Lain" max-width="xl" @close="emit('close')">
        <div class="space-y-4">
            <div class="flex flex-wrap gap-1.5 rounded-[10px] bg-[#F1F5F9] p-1 dark:bg-[#162030]">
                <button
                    type="button"
                    @click="bankModalTab = 'manual'"
                    :class="[
                        'inline-flex min-h-[38px] flex-1 items-center justify-center gap-1.5 rounded-[8px] px-3 text-xs font-semibold transition-colors',
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
                        'inline-flex min-h-[38px] flex-1 items-center justify-center gap-1.5 rounded-[8px] px-3 text-xs font-semibold transition-colors',
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
                        'inline-flex min-h-[38px] flex-1 items-center justify-center gap-1.5 rounded-[8px] px-3 text-xs font-semibold transition-colors',
                        bankModalTab === 'clone'
                            ? 'bg-white text-[#1B2CC1] shadow-sm dark:bg-[#283548] dark:text-[#ABD2FA]'
                            : 'text-[#475569] hover:text-[#1E293B] dark:text-[#CBD5E1]',
                    ]"
                >
                    <Copy class="h-3.5 w-3.5" aria-hidden="true" />
                    Salin dari Kuis Lain
                </button>
            </div>

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
                    <button type="button" @click="toggleSelectAllFiltered" class="font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
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
                    <button type="button" @click="emit('close')" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Tutup</button>
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
                    <button type="button" @click="emit('close')" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="autoPickForm.processing || autoPickMatchingCount === 0" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        {{ autoPickForm.processing ? 'Memproses...' : 'Generate Soal Acak' }}
                    </button>
                </div>
            </form>

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
                    <button type="button" @click="emit('close')" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="cloneForm.processing || otherQuizzes.length === 0" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        {{ cloneForm.processing ? 'Menyalin...' : 'Salin Semua Soal' }}
                    </button>
                </div>
            </form>
        </div>
    </AppModal>
</template>
