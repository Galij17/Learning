<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { FileDown, FileSpreadsheet } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';

interface ReportRow {
    identity_number: string;
    student_name: string;
    student_email: string;
    course_code: string;
    course_title: string;
    category: string;
    period: string;
    instructor: string;
    status: string;
    progress: number;
    assignment_avg: number;
    quiz_avg: number;
    exam_avg: number;
    final_score: number;
    letter_grade: string;
}

const props = defineProps<{
    rows: ReportRow[];
    courses: Array<{ id: number; title: string; code: string }>;
    periods: Array<{ id: number; name: string }>;
    filters: { course_id: number | null; period_id: number | null };
}>();

const courseId = ref<string | number>(props.filters.course_id ?? '');
const periodId = ref<string | number>(props.filters.period_id ?? '');

function applyFilters(): void {
    router.get(
        route('admin.reports.index'),
        { course_id: courseId.value || undefined, period_id: periodId.value || undefined },
        { preserveState: true, replace: true }
    );
}

const exportParams = computed(() => ({
    course_id: courseId.value || undefined,
    period_id: periodId.value || undefined,
}));
</script>

<template>
    <Head title="Laporan Pembelajaran" />

    <AuthenticatedLayout title="Laporan Pembelajaran & Ekspor">
        <div class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-2">
                    <select v-model="courseId" @change="applyFilters" aria-label="Filter kelas" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]">
                        <option value="">Semua Kelas</option>
                        <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.code }} - {{ c.title }}</option>
                    </select>
                    <select v-model="periodId" @change="applyFilters" aria-label="Filter periode" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]">
                        <option value="">Semua Periode</option>
                        <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        :href="route('admin.reports.export-pdf', exportParams)"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] border border-[#1B2CC1] bg-white px-4 text-sm font-semibold text-[#1B2CC1] hover:bg-[#E2E8F0] dark:border-[#ABD2FA] dark:bg-[#283548] dark:text-[#ABD2FA]"
                    >
                        <FileDown class="h-4 w-4" aria-hidden="true" />
                        <span>Unduh PDF</span>
                    </a>
                    <a
                        :href="route('admin.reports.export-excel', exportParams)"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <FileSpreadsheet class="h-4 w-4" aria-hidden="true" />
                        <span>Unduh Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <EmptyState v-if="rows.length === 0" :icon="FileSpreadsheet" title="Belum ada data laporan" description="Belum ada peserta terdaftar untuk kombinasi filter kelas/periode yang dipilih." />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Peserta</th>
                                <th class="px-4 py-3 font-semibold">Kelas</th>
                                <th class="px-4 py-3 font-semibold">Progres</th>
                                <th class="px-4 py-3 font-semibold">Tugas</th>
                                <th class="px-4 py-3 font-semibold">Kuis</th>
                                <th class="px-4 py-3 font-semibold">Ujian</th>
                                <th class="px-4 py-3 font-semibold">Nilai Akhir</th>
                                <th class="px-4 py-3 font-semibold">Mutu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="(r, idx) in rows" :key="idx">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ r.student_name }}</p>
                                    <p class="font-mono text-xs text-[#475569] dark:text-[#CBD5E1]">{{ r.identity_number }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium">{{ r.course_title }}</p>
                                    <p class="font-mono text-xs text-[#475569] dark:text-[#CBD5E1]">{{ r.course_code }} • {{ r.instructor }}</p>
                                </td>
                                <td class="px-4 py-3">{{ r.progress.toFixed(1) }}%</td>
                                <td class="px-4 py-3">{{ r.assignment_avg.toFixed(1) }}</td>
                                <td class="px-4 py-3">{{ r.quiz_avg.toFixed(1) }}</td>
                                <td class="px-4 py-3">{{ r.exam_avg.toFixed(1) }}</td>
                                <td class="px-4 py-3 font-bold">{{ r.final_score.toFixed(1) }}</td>
                                <td class="px-4 py-3 font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">{{ r.letter_grade }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
