<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import type { Course } from '@/types';

const props = defineProps<{
    course: Course;
    categories: Array<{ id: number; name: string }>;
    subjects: Array<{ id: number; name: string; category_id: number }>;
    periods: Array<{ id: number; name: string; is_active: boolean }>;
}>();

const settingsForm = useForm({
    title: props.course.title,
    category_id: props.course.category_id,
    subject_id: props.course.subject_id ?? ('' as number | string),
    academic_period_id: props.course.academic_period_id ?? ('' as number | string),
    description: props.course.description ?? '',
    status: props.course.status,
    assignment_weight: props.course.assignment_weight,
    quiz_weight: props.course.quiz_weight,
    exam_weight: props.course.exam_weight,
    min_completion_percentage: props.course.min_completion_percentage,
    min_passing_score: Number(props.course.min_passing_score),
    certificate_enabled: props.course.certificate_enabled,
    thumbnail: null as File | null,
});

const filteredSubjects = computed(() =>
    props.subjects.filter((s) => !settingsForm.category_id || s.category_id === Number(settingsForm.category_id))
);

const weightTotal = computed(
    () => Number(settingsForm.assignment_weight) + Number(settingsForm.quiz_weight) + Number(settingsForm.exam_weight)
);

function submitSettings(): void {
    settingsForm.post(route('instructor.courses.update', props.course.id), {
        forceFormData: true,
    });
}
</script>

<template>
    <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
        <h3 class="text-base font-bold">Pengaturan Kelas &amp; Bobot Penilaian</h3>
        <form @submit.prevent="submitSettings" class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="s_title" class="mb-1 block text-xs font-semibold">Judul Kelas</label>
                    <input id="s_title" v-model="settingsForm.title" type="text" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                </div>
                <div>
                    <label for="s_status" class="mb-1 block text-xs font-semibold">Status Publikasi</label>
                    <select id="s_status" v-model="settingsForm.status" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="s_cat" class="mb-1 block text-xs font-semibold">Kategori</label>
                    <select id="s_cat" v-model="settingsForm.category_id" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <label for="s_subj" class="mb-1 block text-xs font-semibold">Mata Pelajaran</label>
                    <select id="s_subj" v-model="settingsForm.subject_id" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="">Pilih Mata Pelajaran</option>
                        <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label for="s_period" class="mb-1 block text-xs font-semibold">Periode Akademik</label>
                    <select id="s_period" v-model="settingsForm.academic_period_id" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="">Tanpa Periode</option>
                        <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="s_desc" class="mb-1 block text-xs font-semibold">Deskripsi Kelas</label>
                <textarea id="s_desc" v-model="settingsForm.description" rows="3" class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="s_aw" class="mb-1 block text-xs font-semibold">Bobot Tugas (%)</label>
                    <input id="s_aw" v-model.number="settingsForm.assignment_weight" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                </div>
                <div>
                    <label for="s_qw" class="mb-1 block text-xs font-semibold">Bobot Kuis (%)</label>
                    <input id="s_qw" v-model.number="settingsForm.quiz_weight" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                </div>
                <div>
                    <label for="s_ew" class="mb-1 block text-xs font-semibold">Bobot Ujian (%)</label>
                    <input id="s_ew" v-model.number="settingsForm.exam_weight" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                </div>
            </div>
            <p v-if="weightTotal !== 100" class="text-xs font-semibold text-[#DC2626]">Total bobot harus 100% (saat ini {{ weightTotal }}%).</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="s_comp" class="mb-1 block text-xs font-semibold">Syarat Progress Sertifikat (%)</label>
                    <input id="s_comp" v-model.number="settingsForm.min_completion_percentage" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                </div>
                <div>
                    <label for="s_pass" class="mb-1 block text-xs font-semibold">Nilai Kelulusan Minimum</label>
                    <input id="s_pass" v-model.number="settingsForm.min_passing_score" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input v-model="settingsForm.certificate_enabled" type="checkbox" class="h-4 w-4 rounded" />
                <span>Terbitkan sertifikat otomatis bila siswa memenuhi syarat</span>
            </label>

            <button type="submit" :disabled="settingsForm.processing || weightTotal !== 100" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                Simpan Perubahan Kelas
            </button>
        </form>
    </section>
</template>
