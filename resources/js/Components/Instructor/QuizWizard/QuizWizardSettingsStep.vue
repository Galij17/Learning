<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next';
import InputError from '@/Components/InputError.vue';

export interface QuizWizardSettingsForm {
    lesson_id: number | string;
    title: string;
    description: string;
    type: 'quiz' | 'exam';
    duration_minutes: number;
    starts_at: string;
    ends_at: string;
    max_attempts: number;
    passing_score: number;
    shuffle_questions: boolean;
    shuffle_options: boolean;
    show_results: boolean;
    show_explanations: boolean;
    errors: Partial<Record<string, string>>;
}

defineProps<{
    form: QuizWizardSettingsForm;
    lessons: Array<{ id: number; title: string; meeting_number: number }>;
}>();

const emit = defineEmits<{
    next: [];
}>();
</script>

<template>
    <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
        <form @submit.prevent="emit('next')" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="w_title" class="mb-1 block text-xs font-semibold">Judul Kuis / Ujian</label>
                    <input
                        id="w_title"
                        v-model="form.title"
                        type="text"
                        required
                        placeholder="Contoh: Kuis Bab 1 - Algoritma"
                        class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="w_type" class="mb-1 block text-xs font-semibold">Jenis</label>
                        <select
                            id="w_type"
                            v-model="form.type"
                            class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                        >
                            <option value="quiz">Kuis</option>
                            <option value="exam">Ujian</option>
                        </select>
                    </div>
                    <div>
                        <label for="w_lesson" class="mb-1 block text-xs font-semibold">Bab (Opsional)</label>
                        <select
                            id="w_lesson"
                            v-model="form.lesson_id"
                            class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                        >
                            <option value="">Umum</option>
                            <option v-for="l in lessons" :key="l.id" :value="l.id">P{{ l.meeting_number }}: {{ l.title }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label for="w_desc" class="mb-1 block text-xs font-semibold">Petunjuk Pengerjaan (Opsional)</label>
                <textarea
                    id="w_desc"
                    v-model="form.description"
                    rows="2"
                    placeholder="Instruksi untuk siswa..."
                    class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                ></textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="w_dur" class="mb-1 block text-xs font-semibold">Durasi (menit)</label>
                    <input
                        id="w_dur"
                        v-model.number="form.duration_minutes"
                        type="number"
                        min="1"
                        max="360"
                        required
                        class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
                    <InputError :message="form.errors.duration_minutes" />
                </div>
                <div>
                    <label for="w_att" class="mb-1 block text-xs font-semibold">Maks Percobaan</label>
                    <input
                        id="w_att"
                        v-model.number="form.max_attempts"
                        type="number"
                        min="1"
                        max="20"
                        required
                        class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
                    <InputError :message="form.errors.max_attempts" />
                </div>
                <div>
                    <label for="w_pass" class="mb-1 block text-xs font-semibold">Nilai KKM (%)</label>
                    <input
                        id="w_pass"
                        v-model.number="form.passing_score"
                        type="number"
                        min="0"
                        max="100"
                        required
                        class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
                    <InputError :message="form.errors.passing_score" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="w_start" class="mb-1 block text-xs font-semibold">Mulai (Opsional)</label>
                    <input
                        id="w_start"
                        v-model="form.starts_at"
                        type="datetime-local"
                        class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
                    <InputError :message="form.errors.starts_at" />
                </div>
                <div>
                    <label for="w_end" class="mb-1 block text-xs font-semibold">Selesai (Opsional)</label>
                    <input
                        id="w_end"
                        v-model="form.ends_at"
                        type="datetime-local"
                        class="w-full min-h-[42px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
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
                <button
                    type="submit"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    Lanjut ke Pilih Soal
                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </form>
    </section>
</template>
