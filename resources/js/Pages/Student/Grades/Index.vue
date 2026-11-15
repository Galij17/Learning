<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Award, GraduationCap } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Certificate, Course } from '@/types';

defineProps<{
    grades: Array<{
        id: number;
        course: Course;
        assignment_avg: number;
        quiz_avg: number;
        exam_avg: number;
        final_score: number;
        letter_grade: string | null;
        instructor_feedback: string | null;
        certificate: Certificate | null;
    }>;
}>();
</script>

<template>
    <Head title="Transkrip & Nilai Saya" />

    <AuthenticatedLayout title="Rekap Nilai & Kemajuan Akademik">
        <div class="space-y-6">
            <EmptyState
                v-if="grades.length === 0"
                :icon="GraduationCap"
                title="Belum ada nilai tercatat"
                description="Nilai tugas, kuis, dan ujian dari kelas yang Anda ikuti akan dirangkum otomatis di sini."
            />

            <div v-else class="grid gap-4 md:grid-cols-2">
                <article
                    v-for="row in grades"
                    :key="row.id"
                    class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-xs font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">{{ row.course?.code }}</span>
                            <h3 class="mt-1 text-base font-bold">{{ row.course?.title }}</h3>
                            <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ row.course?.instructor?.name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-extrabold text-[#1B2CC1] dark:text-[#ABD2FA]">{{ row.final_score.toFixed(1) }}</p>
                            <StatusBadge :label="`Grade ${row.letter_grade || '-'}`" variant="mint" />
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2 rounded-[10px] bg-[#F8FAFC] p-3 text-center text-xs dark:bg-[#162030]">
                        <div>
                            <p class="text-[#475569] dark:text-[#CBD5E1]">Tugas ({{ row.course?.assignment_weight }}%)</p>
                            <p class="mt-0.5 font-bold">{{ row.assignment_avg.toFixed(1) }}</p>
                        </div>
                        <div>
                            <p class="text-[#475569] dark:text-[#CBD5E1]">Kuis ({{ row.course?.quiz_weight }}%)</p>
                            <p class="mt-0.5 font-bold">{{ row.quiz_avg.toFixed(1) }}</p>
                        </div>
                        <div>
                            <p class="text-[#475569] dark:text-[#CBD5E1]">Ujian ({{ row.course?.exam_weight }}%)</p>
                            <p class="mt-0.5 font-bold">{{ row.exam_avg.toFixed(1) }}</p>
                        </div>
                    </div>

                    <p v-if="row.instructor_feedback" class="mt-3 text-xs text-[#475569] dark:text-[#CBD5E1]">
                        Catatan Pengajar: {{ row.instructor_feedback }}
                    </p>

                    <div class="mt-4 flex items-center justify-between border-t border-[#E2E8F0] pt-3 dark:border-[#3E4F69]">
                        <Link :href="route('student.courses.show', row.course.id)" class="text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                            Buka Kelas
                        </Link>
                        <Link
                            v-if="row.certificate"
                            :href="route('certificates.show', row.certificate.id)"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                        >
                            <Award class="h-4 w-4" aria-hidden="true" />
                            Sertifikat Tersedia
                        </Link>
                    </div>
                </article>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
