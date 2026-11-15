<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Award, BookOpen, CalendarClock, CheckCircle2, KeyRound, Megaphone, TrendingUp } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BarChartCard from '@/Components/BarChartCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Announcement, Assignment, Enrollment, Grade } from '@/types';

defineProps<{
    stats: {
        enrolled_courses: number;
        completed_courses: number;
        average_progress: number;
        certificates_count: number;
    };
    enrollments: Enrollment[];
    upcomingAssignments: Assignment[];
    recentAnnouncements: Announcement[];
    grades: Grade[];
    progressChart: Array<{ label: string; progress: number; score: number }>;
}>();

const joinForm = useForm({
    code: '',
});

function submitJoin(): void {
    joinForm.post(route('student.courses.join'), {
        onSuccess: () => joinForm.reset(),
    });
}

function formatDate(val?: string | null): string {
    if (!val) return 'Tanpa deadline';
    return new Date(val).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>
    <Head title="Dashboard Pembelajaran" />

    <AuthenticatedLayout title="Dashboard Pembelajaran">
        <div class="space-y-6">
            <!-- Quick Join Bar + Stats -->
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                    <div>
                        <h2 class="text-base font-bold">Gabung Kelas Menggunakan Kode</h2>
                        <p class="mt-0.5 text-xs text-[#475569] dark:text-[#CBD5E1]">Masukkan kode kelas dari pengajar untuk langsung mengakses materi dan evaluasi.</p>
                    </div>
                    <form @submit.prevent="submitJoin" class="flex flex-wrap items-start gap-2">
                        <div>
                            <label for="join_code" class="sr-only">Kode Kelas</label>
                            <div class="relative">
                                <KeyRound class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#475569]" aria-hidden="true" />
                                <input
                                    id="join_code"
                                    v-model="joinForm.code"
                                    type="text"
                                    required
                                    placeholder="Contoh: WEB2026"
                                    class="min-h-[44px] w-48 rounded-[8px] border border-[#E2E8F0] pl-9 pr-3 font-mono text-sm uppercase dark:border-[#1B2CC1] dark:bg-[#162030]"
                                />
                            </div>
                            <p v-if="joinForm.errors.code" class="mt-1 text-xs text-[#DC2626]">{{ joinForm.errors.code }}</p>
                        </div>
                        <button
                            type="submit"
                            :disabled="joinForm.processing"
                            class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white hover:bg-[#142194] disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            Gabung Kelas
                        </button>
                    </form>
                </div>
            </section>

            <!-- Stat Cards -->
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Kelas Diikuti</span>
                        <BookOpen class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.enrolled_courses }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Kelas aktif semester ini</p>
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Rata-rata Progress</span>
                        <TrendingUp class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.average_progress }}%</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Ketuntasan seluruh materi</p>
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Kelas Selesai</span>
                        <CheckCircle2 class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.completed_courses }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Memenuhi kriteria kelulusan</p>
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Sertifikat</span>
                        <Award class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.certificates_count }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Siap diunduh & diverifikasi</p>
                </div>
            </section>

            <!-- Charts & Upcoming Assignments -->
            <section class="grid gap-6 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <BarChartCard
                        title="Progress Kelas (%)"
                        subtitle="Persentase materi selesai di setiap kelas yang diikuti"
                        :labels="progressChart.map((c) => c.label)"
                        dataset-label="Progress (%)"
                        :values="progressChart.map((c) => c.progress)"
                    />
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548] lg:col-span-2">
                    <div class="flex items-center gap-2">
                        <CalendarClock class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                        <h3 class="text-base font-bold">Tugas Mendatang</h3>
                    </div>

                    <EmptyState
                        v-if="upcomingAssignments.length === 0"
                        :icon="CalendarClock"
                        title="Tidak ada tugas aktif"
                        description="Semua tugas kelas sudah selesai atau belum ada deadline mendatang."
                        class="mt-4 border-0 py-8"
                    />

                    <div v-else class="mt-4 space-y-3">
                        <Link
                            v-for="asg in upcomingAssignments"
                            :key="asg.id"
                            :href="route('student.assignments.show', [asg.course_id, asg.id])"
                            class="block rounded-[10px] border border-[#E2E8F0] p-3 transition-colors hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:hover:bg-[#162030]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold">{{ asg.title }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ asg.course?.title }}</p>
                                </div>
                                <StatusBadge
                                    :label="asg.submissions && asg.submissions.length > 0 ? 'Sudah Kirim' : 'Belum'"
                                    :variant="asg.submissions && asg.submissions.length > 0 ? 'mint' : 'amber'"
                                />
                            </div>
                            <p class="mt-1.5 text-[11px] text-[#475569] dark:text-[#CBD5E1]">Deadline: {{ formatDate(asg.due_at) }}</p>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Enrolled Courses & Recent Announcements -->
            <section class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold">Kelas Saya</h3>
                        <Link :href="route('student.courses.index')" class="text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                            Lihat Semua
                        </Link>
                    </div>

                    <EmptyState
                        v-if="enrollments.length === 0"
                        :icon="BookOpen"
                        title="Belum terdaftar di kelas"
                        description="Gunakan form kode kelas di atas untuk bergabung ke kelas pertama Anda."
                    />

                    <div v-else class="grid gap-4 sm:grid-cols-2">
                        <Link
                            v-for="enr in enrollments.slice(0, 4)"
                            :key="enr.id"
                            :href="route('student.courses.show', enr.course_id)"
                            class="rounded-[14px] border border-[#E2E8F0] bg-white p-4 transition-colors hover:border-[#7692FF] dark:border-[#3E4F69] dark:bg-[#283548]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="rounded-[6px] bg-[#F8FAFC] px-2 py-0.5 font-mono text-xs font-bold text-[#1B2CC1] dark:bg-[#3E4F69] dark:text-[#ABD2FA]">
                                    {{ enr.course?.code }}
                                </span>
                                <StatusBadge :label="enr.status" :variant="enr.status === 'completed' ? 'mint' : 'neutral'" />
                            </div>
                            <h4 class="mt-2.5 text-sm font-bold">{{ enr.course?.title }}</h4>
                            <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ enr.course?.instructor?.name }}</p>
                            <div class="mt-3">
                                <div class="mb-1 flex justify-between text-xs">
                                    <span>Progress</span>
                                    <span class="font-bold">{{ Number(enr.progress_percentage).toFixed(0) }}%</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-[#E2E8F0] dark:bg-[#3E4F69]">
                                    <div class="h-full bg-[#7692FF]" :style="{ width: `${Math.min(100, Number(enr.progress_percentage))}%` }" />
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <Megaphone class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                        <h3 class="text-base font-bold">Pengumuman Terbaru</h3>
                    </div>

                    <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]">
                        <p v-if="recentAnnouncements.length === 0" class="py-4 text-center text-xs text-[#475569] dark:text-[#CBD5E1]">
                            Belum ada pengumuman terbaru.
                        </p>
                        <div v-else class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <article v-for="ann in recentAnnouncements" :key="ann.id" class="py-3 first:pt-0 last:pb-0">
                                <p class="text-xs font-bold">{{ ann.title }}</p>
                                <p class="mt-0.5 text-[11px] text-[#475569] dark:text-[#CBD5E1]">
                                    {{ ann.course?.title || 'Global' }} • {{ ann.author?.name }}
                                </p>
                                <p class="mt-1 line-clamp-2 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ ann.content }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
