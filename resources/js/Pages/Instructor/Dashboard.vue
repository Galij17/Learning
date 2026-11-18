<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, ClipboardCheck, FileQuestion, Users } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BarChartCard from '@/Components/BarChartCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Course, QuizAttempt, Submission } from '@/types';

defineProps<{
    stats: {
        courses_count: number;
        total_students: number;
        pending_submissions_count: number;
        pending_essays_count: number;
    };
    courses: Course[];
    pendingSubmissions: Array<Submission & { assignment: { id: number; title: string; course_id: number; course: { id: number; title: string; code: string } } }>;
    pendingEssayAttempts: Array<QuizAttempt & { quiz: { id: number; title: string; course_id: number; course: { id: number; title: string; code: string } } }>;
    chartCourses: Array<{ label: string; students: number; avg_progress: number }>;
}>();
</script>

<template>
    <Head title="Dashboard Pengajar" />

    <AuthenticatedLayout title="Dashboard Pengajar">
        <div class="space-y-6">
            <!-- Priority Stats -->
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Tugas Menunggu Nilai</span>
                        <ClipboardCheck class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.pending_submissions_count }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Pengumpulan tugas belum dinilai</p>
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Essay Perlu Dinilai</span>
                        <FileQuestion class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.pending_essays_count }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Percobaan kuis/ujian dengan essay</p>
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Kelas Diampu</span>
                        <BookOpen class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.courses_count }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Kelas aktif dan draft</p>
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Total Peserta</span>
                        <Users class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.total_students }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Siswa terdaftar di seluruh kelas Anda</p>
                </div>
            </section>

            <!-- Actionable Grading Queue + Chart -->
            <section class="grid gap-6 lg:grid-cols-12">
                <div class="space-y-6 lg:col-span-7">
                    <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                        <h2 class="text-base font-bold">Antrean Penilaian Tugas</h2>
                        <p class="mt-0.5 text-xs text-[#475569] dark:text-[#CBD5E1]">Klik tugas untuk segera memberi nilai dan feedback.</p>

                        <EmptyState
                            v-if="pendingSubmissions.length === 0"
                            class="mt-4"
                            title="Semua tugas sudah dinilai"
                            description="Tidak ada pengumpulan tugas yang menunggu pemeriksaan."
                        />

                        <ul v-else class="mt-4 divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <li v-for="sub in pendingSubmissions" :key="sub.id" class="flex flex-wrap items-center justify-between gap-2 py-3">
                                <div>
                                    <p class="text-sm font-semibold">{{ sub.student?.name }} • {{ sub.assignment.title }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">
                                        {{ sub.assignment.course.code }} • Dikumpulkan {{ new Date(sub.submitted_at).toLocaleString('id-ID') }}
                                    </p>
                                </div>
                                <Link
                                    :href="route('instructor.assignments.show', [sub.assignment.course_id, sub.assignment.id])"
                                    class="inline-flex min-h-[38px] items-center rounded-[8px] bg-[#1B2CC1] px-3 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                                >
                                    Nilai Tugas
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div v-if="pendingEssayAttempts.length > 0" class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                        <h2 class="text-base font-bold">Antrean Penilaian Essay Ujian</h2>
                        <ul class="mt-3 divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <li v-for="att in pendingEssayAttempts" :key="att.id" class="flex flex-wrap items-center justify-between gap-2 py-3">
                                <div>
                                    <p class="text-sm font-semibold">{{ att.student?.name }} • {{ att.quiz.title }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ att.quiz.course.code }} • Percobaan #{{ att.attempt_number }}</p>
                                </div>
                                <Link
                                    :href="route('instructor.quizzes.show', [att.quiz.course_id, att.quiz.id])"
                                    class="inline-flex min-h-[38px] items-center rounded-[8px] border border-[#1B2CC1] px-3 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                                >
                                    Periksa Essay
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <BarChartCard
                        title="Berapa rata-rata progres belajar (%) per kelas Anda?"
                        subtitle="Berdasarkan penyelesaian materi oleh peserta aktif"
                        :labels="chartCourses.map((c) => c.label)"
                        dataset-label="Progres (%)"
                        :values="chartCourses.map((c) => c.avg_progress)"
                    />
                </div>
            </section>

            <!-- Active Courses -->
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-bold">Kelas Anda</h2>
                    <Link
                        :href="route('instructor.courses.index')"
                        class="inline-flex min-h-[40px] items-center rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        Kelola Semua Kelas
                    </Link>
                </div>

                <EmptyState
                    v-if="courses.length === 0"
                    :icon="BookOpen"
                    title="Belum ada kelas yang diampu"
                    description="Buat kelas baru atau hubungi administrator untuk penugasan kelas."
                />

                <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="course in courses.slice(0, 6)"
                        :key="course.id"
                        :href="route('instructor.courses.show', course.id)"
                        class="block rounded-[12px] border border-[#E2E8F0] p-4 transition-colors hover:border-[#7692FF] hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:hover:border-[#ABD2FA] dark:hover:bg-[#1E293B]"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">{{ course.code }}</span>
                            <StatusBadge :label="course.status" :variant="course.status === 'published' ? 'mint' : 'amber'" />
                        </div>
                        <h3 class="mt-2 font-bold">{{ course.title }}</h3>
                        <p class="mt-2 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            {{ course.enrollments_count ?? 0 }} peserta • {{ course.lessons_count ?? 0 }} bab • {{ course.assignments_count ?? 0 }} tugas
                        </p>
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
