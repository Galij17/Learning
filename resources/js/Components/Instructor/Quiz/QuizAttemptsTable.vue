<script setup lang="ts">
import { ClipboardEdit, Users } from 'lucide-vue-next';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { PaginatedData, QuizAttempt } from '@/types';

defineProps<{
    attempts: PaginatedData<QuizAttempt>;
}>();

const emit = defineEmits<{
    gradeEssay: [attempt: QuizAttempt];
}>();
</script>

<template>
    <section class="space-y-3">
        <h3 class="text-base font-bold">Riwayat Percobaan Siswa</h3>
        <EmptyState
            v-if="attempts.data.length === 0"
            :icon="Users"
            title="Belum ada siswa mengerjakan"
            description="Hasil pengerjaan kuis atau ujian siswa akan muncul di tabel ini."
        />

        <div v-else class="overflow-x-auto rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#162030] dark:text-[#CBD5E1]">
                    <tr>
                        <th class="px-4 py-3">Siswa</th>
                        <th class="px-4 py-3">Percobaan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Objektif</th>
                        <th class="px-4 py-3">Essay</th>
                        <th class="px-4 py-3">Nilai (%)</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                    <tr v-for="att in attempts.data" :key="att.id">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ att.student?.name }}</p>
                            <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ att.student?.identity_number || att.student?.email }}</p>
                        </td>
                        <td class="px-4 py-3">#{{ att.attempt_number }}</td>
                        <td class="px-4 py-3">
                            <StatusBadge
                                :label="att.needs_manual_grading ? 'Perlu Nilai Essay' : att.status"
                                :variant="att.needs_manual_grading ? 'amber' : att.is_passed ? 'mint' : 'danger'"
                            />
                        </td>
                        <td class="px-4 py-3">{{ att.objective_score }}</td>
                        <td class="px-4 py-3">{{ att.essay_score }}</td>
                        <td class="px-4 py-3 font-bold">{{ Number(att.percentage).toFixed(1) }}%</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="att.answers && att.answers.some((a) => a.question?.type === 'essay')"
                                type="button"
                                @click="emit('gradeEssay', att)"
                                class="inline-flex min-h-[34px] items-center gap-1 rounded-[8px] bg-[#1B2CC1] px-3 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                            >
                                <ClipboardEdit class="h-3.5 w-3.5" aria-hidden="true" />
                                Nilai Essay
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PaginationBar :links="attempts.links" :from="attempts.from" :to="attempts.to" :total="attempts.total" />
    </section>
</template>
