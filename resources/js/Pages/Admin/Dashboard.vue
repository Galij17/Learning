<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, FileText, GraduationCap, Users } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BarChartCard from '@/Components/BarChartCard.vue';
import LineChartCard from '@/Components/LineChartCard.vue';
import EmptyState from '@/Components/EmptyState.vue';

interface Props {
    stats: {
        total_users: number;
        active_users: number;
        instructors_count: number;
        students_count: number;
        courses_count: number;
        published_courses_count: number;
        materials_count: number;
        assignments_count: number;
        quizzes_count: number;
        enrollments_count: number;
    };
    recentActivities: Array<{
        id: number;
        action: string;
        description: string;
        user_name: string;
        created_at: string;
        created_at_human: string;
    }>;
    charts: {
        courseEnrollments: Array<{ label: string; title: string; count: number }>;
        dailyActivity: { labels: string[]; totals: number[] };
        roleBreakdown: Array<{ label: string; count: number }>;
    };
}

defineProps<Props>();
</script>

<template>
    <Head title="Dashboard Administrator" />

    <AuthenticatedLayout title="Dashboard Administrator">
        <div class="space-y-6">
            <!-- Primary KPI Summary -->
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Total Pengguna</span>
                        <Users class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.total_users }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                        {{ stats.active_users }} aktif • {{ stats.instructors_count }} pengajar • {{ stats.students_count }} siswa
                    </p>
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Kelas Terdaftar</span>
                        <GraduationCap class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.courses_count }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                        {{ stats.published_courses_count }} dipublikasikan • {{ stats.enrollments_count }} kepesertaan
                    </p>
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Materi & Tugas</span>
                        <BookOpen class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.materials_count + stats.assignments_count }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                        {{ stats.materials_count }} materi ajar • {{ stats.assignments_count }} tugas aktif
                    </p>
                </div>

                <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#475569] dark:text-[#CBD5E1]">Kuis & Ujian</span>
                        <FileText class="h-5 w-5 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-3xl font-extrabold">{{ stats.quizzes_count }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Evaluasi objektif & essay terkelola</p>
                </div>
            </section>

            <!-- Charts -->
            <section class="grid gap-6 lg:grid-cols-2">
                <BarChartCard
                    title="Berapa jumlah peserta per kelas teratas?"
                    subtitle="Distribusi pendaftaran mahasiswa aktif pada masing-masing kode kelas"
                    :labels="charts.courseEnrollments.map((c) => c.label)"
                    dataset-label="Peserta"
                    :values="charts.courseEnrollments.map((c) => c.count)"
                />
                <LineChartCard
                    title="Bagaimana tren aktivitas sistem dalam 7 hari terakhir?"
                    subtitle="Jumlah aksi tercatat (login, pengumpulan tugas, ujian, perubahan data)"
                    :labels="charts.dailyActivity.labels"
                    dataset-label="Aktivitas"
                    :values="charts.dailyActivity.totals"
                />
            </section>

            <!-- Recent System Activities -->
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#E2E8F0] px-5 py-4 dark:border-[#3E4F69]">
                    <div>
                        <h2 class="text-base font-bold">Aktivitas Sistem Terbaru</h2>
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Log audit real-time dari aktivitas pengguna di platform.</p>
                    </div>
                    <Link
                        :href="route('admin.activity-logs.index')"
                        class="inline-flex min-h-[40px] items-center rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] hover:bg-[#E2E8F0] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69]"
                    >
                        Lihat Semua Log
                    </Link>
                </div>

                <div v-if="recentActivities.length === 0" class="p-6">
                    <EmptyState title="Belum ada aktivitas tercatat" description="Aktivitas login dan operasional pengguna akan tercatat otomatis di sini." />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Pengguna</th>
                                <th class="px-5 py-3 font-semibold">Aksi</th>
                                <th class="px-5 py-3 font-semibold">Keterangan</th>
                                <th class="px-5 py-3 font-semibold">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="log in recentActivities" :key="log.id">
                                <td class="px-5 py-3 font-medium">{{ log.user_name }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-[#1B2CC1] dark:text-[#ABD2FA]">{{ log.action }}</td>
                                <td class="px-5 py-3">{{ log.description }}</td>
                                <td class="px-5 py-3 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ log.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
