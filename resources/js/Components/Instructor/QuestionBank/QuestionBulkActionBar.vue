<script setup lang="ts">
import { ref } from 'vue';
import { Trash2 } from 'lucide-vue-next';
import type { SubjectWithCount } from './SubjectDirectoryView.vue';

defineProps<{
    selectedCount: number;
    allPageSelected: boolean;
    subjects: SubjectWithCount[];
    instructorQuizzes: Array<{
        id: number;
        course_id: number;
        title: string;
        type: string;
        course?: { id: number; title: string; code: string };
    }>;
    processing: boolean;
}>();

const emit = defineEmits<{
    toggleSelectAll: [];
    moveSubject: [subjectId: number];
    attachQuiz: [quizId: number];
    requestBulkDelete: [];
}>();

const bulkMoveSubjectId = ref<string>('');
const bulkQuizId = ref<string>('');

function handleMoveSubject(): void {
    if (!bulkMoveSubjectId.value) return;
    emit('moveSubject', Number(bulkMoveSubjectId.value));
    bulkMoveSubjectId.value = '';
}

function handleAttachQuiz(): void {
    if (!bulkQuizId.value) return;
    emit('attachQuiz', Number(bulkQuizId.value));
    bulkQuizId.value = '';
}
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[12px] border border-[#E2E8F0] bg-[#F8FAFC] px-4 py-2.5 text-xs dark:border-[#3E4F69] dark:bg-[#162030]">
        <label class="inline-flex cursor-pointer items-center gap-2 font-semibold">
            <input type="checkbox" :checked="allPageSelected" @change="emit('toggleSelectAll')" class="h-4 w-4 rounded" />
            <span>Pilih Semua di Halaman Ini ({{ selectedCount }} dipilih)</span>
        </label>

        <div v-if="selectedCount > 0" class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1">
                <select v-model="bulkMoveSubjectId" aria-label="Pindahkan ke mapel" class="min-h-[34px] rounded-[8px] border border-[#E2E8F0] bg-white px-2 text-xs dark:border-[#1B2CC1] dark:bg-[#283548]">
                    <option value="">Pindah ke Mapel...</option>
                    <option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.code }} — {{ s.name }}</option>
                </select>
                <button
                    v-if="bulkMoveSubjectId"
                    type="button"
                    :disabled="processing"
                    @click="handleMoveSubject"
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
                    :disabled="processing"
                    @click="handleAttachQuiz"
                    class="min-h-[34px] rounded-[8px] bg-[#1B2CC1] px-2.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    Tambahkan
                </button>
            </div>

            <button
                type="button"
                :disabled="processing"
                @click="emit('requestBulkDelete')"
                class="inline-flex min-h-[34px] items-center gap-1 rounded-[8px] bg-red-600 px-3 text-xs font-semibold text-white hover:bg-red-700"
            >
                <Trash2 class="h-3.5 w-3.5" aria-hidden="true" />
                Hapus ({{ selectedCount }})
            </button>
        </div>
    </div>
</template>
