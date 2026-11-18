<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import type { SharedPageProps } from '@/types';

const page = usePage<SharedPageProps>();
const appSettings = computed(() => page.props.appSettings);

const form = useForm({
    name: '',
    email: '',
    identity_number: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(route('register.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Daftar Akun Siswa" />

    <div class="flex min-h-screen flex-col justify-center bg-[#F8FAFC] px-4 py-10 text-[#1E293B] dark:bg-[#1E293B] dark:text-[#F8FAFC]">
        <div class="mx-auto w-full max-w-md rounded-[14px] border border-[#E2E8F0] bg-white p-6 shadow-sm dark:border-[#3E4F69] dark:bg-[#283548] sm:p-7">
            <h1 class="text-xl font-bold">Pendaftaran Akun Siswa</h1>
            <p class="mt-1 text-sm text-[#475569] dark:text-[#CBD5E1]">Daftar untuk mengikuti kelas di {{ appSettings.site_name }}.</p>

            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <label for="name" class="block text-sm font-semibold">Nama Lengkap</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Nama lengkap Anda"
                        class="mt-1.5 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 py-2.5 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="email@example.com"
                        class="mt-1.5 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 py-2.5 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="identity_number" class="block text-sm font-semibold">NIM / NIS (Opsional)</label>
                        <input
                            id="identity_number"
                            v-model="form.identity_number"
                            type="text"
                            placeholder="Contoh: 2026001"
                            class="mt-1.5 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 py-2.5 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]"
                        />
                        <p v-if="form.errors.identity_number" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.identity_number }}</p>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-semibold">No. HP (Opsional)</label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="text"
                            placeholder="08..."
                            class="mt-1.5 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 py-2.5 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]"
                        />
                        <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold">Kata Sandi</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        placeholder="Minimal 8 karakter (huruf & angka)"
                        class="mt-1.5 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 py-2.5 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold">Konfirmasi Kata Sandi</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        placeholder="Ulangi kata sandi"
                        class="mt-1.5 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 py-2.5 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]"
                    />
                    <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.password_confirmation }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex min-h-[44px] w-full items-center justify-center rounded-[10px] bg-[#1B2CC1] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#142194] disabled:opacity-60 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    {{ form.processing ? 'Mendaftarkan...' : 'Buat Akun Siswa' }}
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-[#475569] dark:text-[#CBD5E1]">
                Sudah punya akun?
                <Link :href="route('login')" class="font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                    Masuk di sini
                </Link>
            </p>
        </div>
    </div>
</template>
