<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Moon, Sun } from 'lucide-vue-next';
import { useTheme } from '@/composables/useTheme';
import type { SharedPageProps } from '@/types';

const page = usePage<SharedPageProps>();
const { isDark, toggleTheme } = useTheme();
const appSettings = computed(() => page.props.appSettings);
const flash = computed(() => page.props.flash);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit(): void {
    form.post(route('login.store'), {
        onFinish: () => form.reset('password'),
    });
}

function fillDemo(email: string): void {
    form.email = email;
    form.password = 'Password123!';
}
</script>

<template>
    <Head title="Masuk" />

    <div class="flex min-h-screen flex-col justify-center bg-[#F8FAFC] px-4 py-10 text-[#1E293B] dark:bg-[#1E293B] dark:text-[#F8FAFC]">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-6 flex items-center justify-between">
                <Link :href="route('home')" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-[10px] bg-[#1B2CC1] text-sm font-extrabold text-[#ABD2FA]">
                        {{ appSettings.site_name.slice(0, 2).toUpperCase() }}
                    </span>
                    <div>
                        <p class="text-base font-bold">{{ appSettings.site_name }}</p>
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ appSettings.institution_name }}</p>
                    </div>
                </Link>
                <button
                    type="button"
                    @click="toggleTheme"
                    :aria-label="isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-[10px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <Sun v-if="isDark" class="h-5 w-5 text-[#ABD2FA]" aria-hidden="true" />
                    <Moon v-else class="h-5 w-5 text-[#1B2CC1]" aria-hidden="true" />
                </button>
            </div>

            <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 shadow-sm dark:border-[#3E4F69] dark:bg-[#283548] sm:p-7">
                <div
                    v-if="flash?.error || flash?.warning || flash?.success"
                    class="mb-4 rounded-[8px] border px-3.5 py-2.5 text-xs font-medium"
                    :class="
                        flash?.error
                            ? 'border-red-300 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300'
                            : flash?.warning
                              ? 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-300'
                              : 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300'
                    "
                >
                    {{ flash?.error || flash?.warning || flash?.success }}
                </div>

                <h1 class="text-xl font-bold">Masuk ke Portal Belajar</h1>
                <p class="mt-1 text-sm text-[#475569] dark:text-[#CBD5E1]">Masukkan email dan kata sandi akun Anda.</p>

                <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-semibold">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="email@example.com"
                            class="mt-1.5 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 py-2.5 text-sm text-[#1E293B] dark:border-[#1B2CC1] dark:bg-[#1E293B] dark:text-[#F8FAFC]"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold">Kata Sandi</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi"
                            class="mt-1.5 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 py-2.5 text-sm text-[#1E293B] dark:border-[#1B2CC1] dark:bg-[#1E293B] dark:text-[#F8FAFC]"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="inline-flex min-h-[44px] items-center gap-2 text-sm">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-[#1B2CC1] text-[#1B2CC1]"
                            />
                            <span>Ingat sesi saya</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex min-h-[44px] w-full items-center justify-center rounded-[10px] bg-[#1B2CC1] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#142194] disabled:opacity-60 dark:bg-[#ABD2FA] dark:text-[#1E293B] dark:hover:bg-[#93C2F5]"
                    >
                        {{ form.processing ? 'Memverifikasi...' : 'Masuk' }}
                    </button>
                </form>

                <!-- Quick Demo Account Selector (only enabled in local/testing/demo mode) -->
                <div v-if="appSettings.demo_mode" class="mt-6 border-t border-[#E2E8F0] pt-4 dark:border-[#3E4F69]">
                    <p class="text-xs font-semibold text-[#475569] dark:text-[#CBD5E1]">Isi cepat akun demo (kata sandi: Password123!):</p>
                    <div class="mt-2.5 grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            @click="fillDemo('admin@verdantlms.test')"
                            class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-2 text-xs font-semibold hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]"
                        >
                            Admin
                        </button>
                        <button
                            type="button"
                            @click="fillDemo('dosen@verdantlms.test')"
                            class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-2 text-xs font-semibold hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]"
                        >
                            Pengajar
                        </button>
                        <button
                            type="button"
                            @click="fillDemo('siswa@verdantlms.test')"
                            class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-2 text-xs font-semibold hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]"
                        >
                            Siswa
                        </button>
                    </div>
                </div>

                <p v-if="appSettings.allow_registration" class="mt-5 text-center text-sm text-[#475569] dark:text-[#CBD5E1]">
                    Belum punya akun siswa?
                    <Link :href="route('register')" class="font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                        Daftar di sini
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>
