<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/Components/AppModal.vue';
import InputError from '@/Components/InputError.vue';
import { toDateTimeLocalInput } from '@/utils/format';
import type { Quiz } from '@/types';

const props = defineProps<{
    show: boolean;
    courseId: number;
    quiz: Quiz;
}>();

const emit = defineEmits<{
    close: [];
}>();

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

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) return;
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
    }
);

function submitSettings(): void {
    settingsForm.put(route('instructor.quizzes.update', [props.courseId, props.quiz.id]), {
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <AppModal :show="show" title="Pengaturan Kuis / Ujian" @close="emit('close')">
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
            <div class="grid gap-2 text-xs sm:grid-cols-2">
                <label class="flex items-center gap-2"><input v-model="settingsForm.shuffle_questions" type="checkbox" class="h-4 w-4 rounded" /> Acak soal</label>
                <label class="flex items-center gap-2"><input v-model="settingsForm.shuffle_options" type="checkbox" class="h-4 w-4 rounded" /> Acak pilihan</label>
                <label class="flex items-center gap-2"><input v-model="settingsForm.show_results" type="checkbox" class="h-4 w-4 rounded" /> Tampilkan hasil</label>
                <label class="flex items-center gap-2"><input v-model="settingsForm.show_explanations" type="checkbox" class="h-4 w-4 rounded" /> Tampilkan pembahasan</label>
                <label class="flex items-center gap-2"><input v-model="settingsForm.is_published" type="checkbox" class="h-4 w-4 rounded" /> Publikasikan ke siswa</label>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="emit('close')" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                <button type="submit" :disabled="settingsForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Simpan</button>
            </div>
        </form>
    </AppModal>
</template>
