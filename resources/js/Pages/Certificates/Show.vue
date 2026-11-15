<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Download, ShieldCheck } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Certificate } from '@/types';

defineProps<{
    certificate: Certificate;
}>();
</script>

<template>
    <Head :title="`Sertifikat ${certificate.certificate_number}`" />

    <AuthenticatedLayout title="Detail Sertifikat">
        <div class="mx-auto max-w-4xl space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="route('certificates.index')"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] border border-[#E2E8F0] bg-white px-4 text-sm font-semibold hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Kembali</span>
                </Link>
                <a
                    :href="route('certificates.download', certificate.id)"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    <Download class="h-4 w-4" aria-hidden="true" />
                    <span>Unduh PDF Sertifikat</span>
                </a>
            </div>

            <div class="rounded-[14px] border-4 border-[#1B2CC1] bg-white p-8 text-center shadow-sm dark:border-[#ABD2FA] dark:bg-[#283548] sm:p-12">
                <p class="text-xs font-bold tracking-widest uppercase text-[#1B2CC1] dark:text-[#ABD2FA]">Sertifikat Kelulusan</p>
                <h2 class="mt-4 text-2xl font-extrabold sm:text-3xl">{{ certificate.student?.name }}</h2>
                <p class="mt-1 font-mono text-xs text-[#475569] dark:text-[#CBD5E1]">{{ certificate.student?.identity_number }}</p>
                <p class="mt-6 text-sm text-[#475569] dark:text-[#CBD5E1]">Telah menyelesaikan seluruh materi dan evaluasi pada kelas:</p>
                <h3 class="mt-2 text-xl font-bold text-[#1E293B] dark:text-[#F8FAFC]">{{ certificate.course?.title }}</h3>
                <p class="mt-1 text-sm text-[#475569] dark:text-[#CBD5E1]">Kode Kelas: {{ certificate.course?.code }} • Pengajar: {{ certificate.course?.instructor?.name }}</p>

                <div class="mt-6 inline-flex items-center gap-2 rounded-[8px] bg-[#ABD2FA] px-4 py-2 text-sm font-bold text-[#1E293B]">
                    <span>Nilai Akhir: {{ Number(certificate.final_score).toFixed(2) }} / 100</span>
                </div>

                <div class="mt-8 flex flex-col items-center justify-between gap-4 border-t border-[#E2E8F0] pt-6 text-xs text-[#475569] dark:border-[#3E4F69] dark:text-[#CBD5E1] sm:flex-row">
                    <span>No. Sertifikat: <strong class="font-mono text-[#1E293B] dark:text-[#F8FAFC]">{{ certificate.certificate_number }}</strong></span>
                    <span class="inline-flex items-center gap-1.5">
                        <ShieldCheck class="h-4 w-4 text-[#7692FF] dark:text-[#ABD2FA]" aria-hidden="true" />
                        Hash Verifikasi: <code class="font-mono">{{ certificate.verification_hash.slice(0, 16) }}</code>
                    </span>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
