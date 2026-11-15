<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Award, Download, Eye } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Certificate, PaginatedData } from '@/types';

defineProps<{
    certificates: PaginatedData<Certificate>;
}>();
</script>

<template>
    <Head title="Sertifikat Saya" />

    <AuthenticatedLayout title="Sertifikat Kelulusan">
        <div class="space-y-5">
            <EmptyState
                v-if="certificates.data.length === 0"
                :icon="Award"
                title="Belum ada sertifikat diterbitkan"
                description="Sertifikat akan terbit otomatis setelah Anda memenuhi persentase penyelesaian materi dan nilai kelulusan minimum pada kelas."
            >
                <template #action>
                    <Link
                        :href="route('student.courses.index')"
                        class="inline-flex min-h-[44px] items-center rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        Lihat Kelas Saya
                    </Link>
                </template>
            </EmptyState>

            <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="cert in certificates.data"
                    :key="cert.id"
                    class="flex flex-col justify-between rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <StatusBadge :label="cert.certificate_number" variant="mint" />
                            <span class="font-mono text-sm font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">
                                {{ Number(cert.final_score).toFixed(1) }}
                            </span>
                        </div>
                        <h3 class="mt-3 text-base font-bold">{{ cert.course?.title }}</h3>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            Pengajar: {{ cert.course?.instructor?.name }} • Terbit {{ new Date(cert.issued_at).toLocaleDateString('id-ID') }}
                        </p>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <Link
                            :href="route('certificates.show', cert.id)"
                            class="inline-flex min-h-[40px] flex-1 items-center justify-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3 text-xs font-semibold text-[#1B2CC1] hover:bg-[#E2E8F0] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69]"
                        >
                            <Eye class="h-4 w-4" aria-hidden="true" />
                            <span>Pratinjau</span>
                        </Link>
                        <a
                            :href="route('certificates.download', cert.id)"
                            class="inline-flex min-h-[40px] flex-1 items-center justify-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            <Download class="h-4 w-4" aria-hidden="true" />
                            <span>Unduh PDF</span>
                        </a>
                    </div>
                </article>
            </div>

            <PaginationBar v-if="certificates.data.length > 0" :links="certificates.links" :from="certificates.from" :to="certificates.to" :total="certificates.total" />
        </div>
    </AuthenticatedLayout>
</template>
