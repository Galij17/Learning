<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BookOpen, CheckCircle2, FileCheck2, Moon, ShieldCheck, Sun } from 'lucide-vue-next';
import { useTheme } from '@/composables/useTheme';
import type { SharedPageProps } from '@/types';

const page = usePage<SharedPageProps>();
const { isDark, toggleTheme } = useTheme();
const appSettings = computed(() => page.props.appSettings);
</script>

<template>
    <Head :title="appSettings.site_name" />

    <div class="min-h-screen bg-[#F8FAFC] text-[#1E293B] dark:bg-[#1E293B] dark:text-[#F8FAFC]">
        <header class="border-b border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-[10px] bg-[#1B2CC1] text-sm font-extrabold text-[#ABD2FA]">
                        {{ appSettings.site_name.slice(0, 2).toUpperCase() }}
                    </span>
                    <div>
                        <p class="text-sm font-bold leading-none">{{ appSettings.site_name }}</p>
                        <p class="mt-0.5 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ appSettings.institution_name }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="toggleTheme"
                        :aria-label="isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-[10px] border border-[#E2E8F0] bg-white text-[#1E293B] hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69]"
                    >
                        <Sun v-if="isDark" class="h-5 w-5" aria-hidden="true" />
                        <Moon v-else class="h-5 w-5" aria-hidden="true" />
                    </button>
                    <Link
                        :href="route('login')"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B] dark:hover:bg-[#93C2F5]"
                    >
                        Masuk Portal
                    </Link>
                    <Link
                        v-if="appSettings.allow_registration"
                        :href="route('register')"
                        class="hidden min-h-[44px] items-center justify-center rounded-[10px] border border-[#1B2CC1] px-4 text-sm font-semibold text-[#1B2CC1] hover:bg-[#E2E8F0] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69] sm:inline-flex"
                    >
                        Daftar Siswa
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:py-16">
            <div class="grid gap-10 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-7">
                    <h1 class="text-3xl font-extrabold tracking-tight text-[#1E293B] dark:text-[#F8FAFC] sm:text-4xl lg:text-5xl">
                        Ruang Belajar Digital Terstruktur untuk Pengajar dan Peserta Didik
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-[#475569] dark:text-[#CBD5E1] sm:text-lg">
                        {{ appSettings.site_tagline }}. Kelola materi perkuliahan per pertemuan, tugas terukur, ujian dengan
                        pengatur waktu server, penilaian berbobot, serta penerbitan sertifikat dalam satu sistem.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="route('login')"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-[10px] bg-[#1B2CC1] px-6 text-sm font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            Masuk ke Akun Anda
                        </Link>
                        <Link
                            v-if="appSettings.allow_registration"
                            :href="route('register')"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-[10px] border border-[#1B2CC1] bg-white px-6 text-sm font-semibold text-[#1B2CC1] hover:bg-[#E2E8F0] dark:border-[#7692FF] dark:bg-[#283548] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69]"
                        >
                            Buat Akun Siswa
                        </Link>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 shadow-sm dark:border-[#3E4F69] dark:bg-[#283548]">
                        <h2 class="text-base font-bold text-[#1E293B] dark:text-[#F8FAFC]">Alur Kerja Tiga Peran</h2>
                        <ul class="mt-4 space-y-4 text-sm">
                            <li class="flex items-start gap-3">
                                <ShieldCheck class="mt-0.5 h-5 w-5 shrink-0 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                                <div>
                                    <p class="font-semibold">Administrator</p>
                                    <p class="text-[#475569] dark:text-[#CBD5E1]">Mengelola akun, kategori, mata pelajaran, periode akademik, log aktivitas, dan ekspor laporan PDF/Excel.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <BookOpen class="mt-0.5 h-5 w-5 shrink-0 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                                <div>
                                    <p class="font-semibold">Pengajar / Dosen</p>
                                    <p class="text-[#475569] dark:text-[#CBD5E1]">Menyusun kelas dengan kode gabung, mengunggah materi privat, bank soal, ujian terjadwal, serta menilai tugas dan essay.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <FileCheck2 class="mt-0.5 h-5 w-5 shrink-0 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                                <div>
                                    <p class="font-semibold">Siswa / Mahasiswa</p>
                                    <p class="text-[#475569] dark:text-[#CBD5E1]">Belajar per bab, mengumpulkan tugas, mengerjakan ujian berwaktu dengan autosave, dan mengunduh sertifikat kelulusan.</p>
                                </div>
                            </li>
                        </ul>
                        <div class="mt-5 flex items-center gap-2 rounded-[10px] bg-[#F8FAFC] p-3 text-xs text-[#475569] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <CheckCircle2 class="h-4 w-4 shrink-0 text-[#7692FF] dark:text-[#ABD2FA]" aria-hidden="true" />
                            <span>Akun demo tersedia di halaman Masuk untuk pengujian langsung tiap peran.</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
