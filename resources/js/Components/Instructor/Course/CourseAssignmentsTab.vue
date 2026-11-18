<script setup lang="ts">
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ClipboardCheck, Plus, Trash2 } from 'lucide-vue-next';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/format';
import type { Assignment } from '@/types';

const props = defineProps<{
    courseId: number;
    assignments: Assignment[];
}>();

const emit = defineEmits<{
    deleteAssignment: [assignmentId: number];
}>();

const showAssignmentModal = ref(false);

const assignmentForm = useForm({
    lesson_id: '' as number | string,
    title: '',
    description: '',
    attachment: null as File | null,
    due_at: '',
    max_score: 100,
    submission_type: 'both' as 'both' | 'file' | 'text',
    allow_late_submission: false,
    is_published: true,
});

function submitAssignment(): void {
    assignmentForm.post(route('instructor.assignments.store', props.courseId), {
        forceFormData: true,
        onSuccess: () => {
            showAssignmentModal.value = false;
            assignmentForm.reset();
        },
    });
}
</script>

<template>
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold">Daftar Tugas Kelas</h3>
            <button type="button" @click="showAssignmentModal = true" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                <Plus class="h-4 w-4" aria-hidden="true" />
                Buat Tugas
            </button>
        </div>

        <EmptyState
            v-if="assignments.length === 0"
            :icon="ClipboardCheck"
            title="Belum ada tugas"
            description="Buat tugas baru lengkap dengan deadline, lampiran, dan batas nilai."
        />

        <div v-else class="grid gap-4 md:grid-cols-2">
            <article
                v-for="item in assignments"
                :key="item.id"
                class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h4 class="text-base font-bold">{{ item.title }}</h4>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            Deadline: {{ formatDate(item.due_at) }} • Maks: {{ item.max_score }} poin
                        </p>
                    </div>
                    <StatusBadge :label="`${item.submissions_count ?? 0} dikumpulkan`" variant="neutral" />
                </div>
                <p class="mt-3 line-clamp-2 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ item.description }}</p>
                <div class="mt-4 flex items-center justify-between border-t border-[#E2E8F0] pt-3 dark:border-[#3E4F69]">
                    <Link :href="route('instructor.assignments.show', [courseId, item.id])" class="inline-flex min-h-[38px] items-center rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        Periksa &amp; Beri Nilai
                    </Link>
                    <button type="button" @click="emit('deleteAssignment', item.id)" aria-label="Hapus tugas" class="rounded-[8px] p-2 text-[#DC2626] hover:bg-[#FEE2E2]">
                        <Trash2 class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </article>
        </div>

        <AppModal :show="showAssignmentModal" title="Buat Tugas Baru" @close="showAssignmentModal = false">
            <form @submit.prevent="submitAssignment" class="space-y-4">
                <div>
                    <label for="a_title" class="mb-1 block text-xs font-semibold">Judul Tugas</label>
                    <input id="a_title" v-model="assignmentForm.title" type="text" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    <InputError :message="assignmentForm.errors.title" />
                </div>
                <div>
                    <label for="a_desc" class="mb-1 block text-xs font-semibold">Instruksi Tugas</label>
                    <textarea id="a_desc" v-model="assignmentForm.description" rows="3" required class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                    <InputError :message="assignmentForm.errors.description" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="a_due" class="mb-1 block text-xs font-semibold">Batas Waktu</label>
                        <input id="a_due" v-model="assignmentForm.due_at" type="datetime-local" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="assignmentForm.errors.due_at" />
                    </div>
                    <div>
                        <label for="a_max" class="mb-1 block text-xs font-semibold">Nilai Maksimal</label>
                        <input id="a_max" v-model.number="assignmentForm.max_score" type="number" min="1" max="1000" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="assignmentForm.errors.max_score" />
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="a_stype" class="mb-1 block text-xs font-semibold">Tipe Pengumpulan</label>
                        <select id="a_stype" v-model="assignmentForm.submission_type" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                            <option value="both">File &amp; Teks</option>
                            <option value="file">Hanya File</option>
                            <option value="text">Hanya Teks</option>
                        </select>
                        <InputError :message="assignmentForm.errors.submission_type" />
                    </div>
                    <div>
                        <label for="a_att" class="mb-1 block text-xs font-semibold">Lampiran Soal (Opsional)</label>
                        <input id="a_att" type="file" @change="assignmentForm.attachment = ($event.target as HTMLInputElement).files?.[0] ?? null" class="w-full text-xs" />
                        <InputError :message="assignmentForm.errors.attachment" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="assignmentForm.allow_late_submission" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Izinkan pengumpulan terlambat</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAssignmentModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="assignmentForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Terbitkan Tugas</button>
                </div>
            </form>
        </AppModal>
    </section>
</template>
