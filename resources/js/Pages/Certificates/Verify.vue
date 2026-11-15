<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, ShieldCheck } from 'lucide-vue-next';
import type { Certificate } from '@/types';

defineProps<{
    certificate: Certificate | null;
    hash: string;
}>();
</script>

<template>
    <Head title="Verifikasi Sertifikat" />

    <div class="flex min-h-screen items-center justify-center bg-[#F8FAFC] p-4 text-[#1E293B] dark:bg-[#1E293B] dark:text-[#F8FAFC]">
        <div class="w-full max-w-lg rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
            <div v-if="certificate" class="space-y-4">
                <div class="flex items-center gap-3 text-[#1B2CC1] dark:text-[#ABD2FA]">
                    <ShieldCheck class="h-7 w-7" aria-hidden="true" />
                    <h1 class="text-xl font-bold">Sertifikat Terverifikasi</h1>
                </div>
                <dl class="space-y-2 rounded-[10px] bg-[#F8FAFC] p-4 text-sm dark:bg-[#1E293B]">
                    <div><dt class="text-xs text-[#475569] dark:text-[#CBD5E1]">Nomor</dt><dd class="font-mono font-bold">{{ certificate.certificate_number }}</dd></div>
                    <div><dt class="text-xs text-[#475569] dark:text-[#CBD5E1]">Nama Peserta</dt><dd class="font-semibold">{{ certificate.student?.name }}</dd></div>
                    <div><dt class="text-xs text-[#475569] dark:text-[#CBD5E1]">Kelas</dt><dd class="font-semibold">{{ certificate.course?.title }}</dd></div>
                    <div><dt class="text-xs text-[#475569] dark:text-[#CBD5E1]">Nilai Akhir</dt><dd class="font-bold">{{ Number(certificate.final_score).toFixed(2) }}</dd></div>
                </dl>
            </div>
            <div v-else class="space-y-3">
                <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                    <AlertCircle class="h-7 w-7" aria-hidden="true" />
                    <h1 class="text-xl font-bold">Sertifikat Tidak Ditemukan</h1>
                </div>
                <p class="text-sm text-[#475569] dark:text-[#CBD5E1]">Kode verifikasi <code class="font-mono">{{ hash }}</code> tidak terdaftar di sistem.</p>
            </div>
            <Link :href="route('home')" class="mt-5 inline-flex min-h-[44px] items-center rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                Kembali ke Beranda
            </Link>
        </div>
    </div>
</template>
