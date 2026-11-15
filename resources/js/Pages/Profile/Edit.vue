<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { SharedPageProps } from '@/types';

const page = usePage<SharedPageProps>();
const user = computed(() => page.props.auth.user);

const profileForm = useForm({
    name: user.value?.name ?? '',
    email: user.value?.email ?? '',
    identity_number: user.value?.identity_number ?? '',
    phone: user.value?.phone ?? '',
    bio: user.value?.bio ?? '',
    avatar: null as File | null,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submitProfile(): void {
    profileForm.post(route('profile.update'), {
        forceFormData: true,
    });
}

function submitPassword(): void {
    passwordForm.put(route('profile.password'), {
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <Head title="Profil Saya" />

    <AuthenticatedLayout title="Profil & Keamanan Akun">
        <div class="mx-auto grid max-w-4xl gap-6 lg:grid-cols-2">
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
                <h2 class="text-lg font-bold">Informasi Profil</h2>
                <form @submit.prevent="submitProfile" class="mt-5 space-y-4">
                    <div>
                        <label for="p-name" class="block text-sm font-semibold">Nama Lengkap</label>
                        <input id="p-name" v-model="profileForm.name" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <p v-if="profileForm.errors.name" class="mt-1 text-xs text-red-600">{{ profileForm.errors.name }}</p>
                    </div>
                    <div>
                        <label for="p-email" class="block text-sm font-semibold">Email</label>
                        <input id="p-email" v-model="profileForm.email" type="email" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <p v-if="profileForm.errors.email" class="mt-1 text-xs text-red-600">{{ profileForm.errors.email }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="p-id" class="block text-sm font-semibold">NIM / NIP</label>
                            <input id="p-id" v-model="profileForm.identity_number" type="text" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        </div>
                        <div>
                            <label for="p-phone" class="block text-sm font-semibold">Telepon</label>
                            <input id="p-phone" v-model="profileForm.phone" type="text" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        </div>
                    </div>
                    <div>
                        <label for="p-bio" class="block text-sm font-semibold">Bio Singkat</label>
                        <textarea id="p-bio" v-model="profileForm.bio" rows="3" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                    <div>
                        <label for="p-avatar" class="block text-sm font-semibold">Foto Profil (JPG/PNG/WebP)</label>
                        <input
                            id="p-avatar"
                            type="file"
                            accept="image/*"
                            @change="(e) => (profileForm.avatar = (e.target as HTMLInputElement).files?.[0] ?? null)"
                            class="mt-1 block w-full text-sm"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="profileForm.processing"
                        class="min-h-[44px] rounded-[10px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        {{ profileForm.processing ? 'Menyimpan...' : 'Simpan Profil' }}
                    </button>
                </form>
            </section>

            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
                <h2 class="text-lg font-bold">Ubah Kata Sandi</h2>
                <form @submit.prevent="submitPassword" class="mt-5 space-y-4">
                    <div>
                        <label for="cur-pw" class="block text-sm font-semibold">Kata Sandi Saat Ini</label>
                        <input id="cur-pw" v-model="passwordForm.current_password" type="password" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-red-600">{{ passwordForm.errors.current_password }}</p>
                    </div>
                    <div>
                        <label for="new-pw" class="block text-sm font-semibold">Kata Sandi Baru</label>
                        <input id="new-pw" v-model="passwordForm.password" type="password" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-600">{{ passwordForm.errors.password }}</p>
                    </div>
                    <div>
                        <label for="conf-pw" class="block text-sm font-semibold">Konfirmasi Kata Sandi Baru</label>
                        <input id="conf-pw" v-model="passwordForm.password_confirmation" type="password" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    </div>
                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="min-h-[44px] rounded-[10px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        {{ passwordForm.processing ? 'Memperbarui...' : 'Perbarui Kata Sandi' }}
                    </button>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
