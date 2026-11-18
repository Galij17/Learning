<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps<{
    settings: {
        site_name: string;
        site_tagline: string;
        institution_name: string;
        support_email: string;
        allow_registration: boolean;
        default_passing_score: number;
        site_logo_url: string | null;
    };
}>();

const form = useForm({
    site_name: props.settings.site_name,
    site_tagline: props.settings.site_tagline,
    institution_name: props.settings.institution_name,
    support_email: props.settings.support_email,
    allow_registration: props.settings.allow_registration,
    default_passing_score: props.settings.default_passing_score,
    site_logo: null as File | null,
});

function submit(): void {
    form.post(route('admin.settings.update'), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Pengaturan Aplikasi" />

    <AuthenticatedLayout title="Pengaturan & Branding Aplikasi">
        <div class="mx-auto max-w-3xl rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
            <form @submit.prevent="submit" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="st-name" class="block text-sm font-semibold">Nama Aplikasi</label>
                        <input id="st-name" v-model="form.site_name" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <InputError :message="form.errors.site_name" />
                    </div>
                    <div>
                        <label for="st-inst" class="block text-sm font-semibold">Nama Institusi</label>
                        <input id="st-inst" v-model="form.institution_name" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <InputError :message="form.errors.institution_name" />
                    </div>
                </div>

                <div>
                    <label for="st-tag" class="block text-sm font-semibold">Tagline Platform</label>
                    <input id="st-tag" v-model="form.site_tagline" type="text" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <InputError :message="form.errors.site_tagline" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="st-email" class="block text-sm font-semibold">Email Dukungan</label>
                        <input id="st-email" v-model="form.support_email" type="email" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <InputError :message="form.errors.support_email" />
                    </div>
                    <div>
                        <label for="st-pass" class="block text-sm font-semibold">Nilai Kelulusan Default (%)</label>
                        <input id="st-pass" v-model="form.default_passing_score" type="number" min="0" max="100" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <InputError :message="form.errors.default_passing_score" />
                    </div>
                </div>

                <div>
                    <label for="st-logo" class="block text-sm font-semibold">Logo Aplikasi (Opsional)</label>
                    <div v-if="settings.site_logo_url" class="my-2">
                        <img :src="settings.site_logo_url" alt="Logo saat ini" width="48" height="48" class="h-12 w-12 rounded-[8px] object-cover" />
                    </div>
                    <input
                        id="st-logo"
                        type="file"
                        accept="image/*"
                        @change="(e) => (form.site_logo = (e.target as HTMLInputElement).files?.[0] ?? null)"
                        class="mt-1 block w-full text-sm"
                    />
                    <InputError :message="form.errors.site_logo" />
                </div>

                <label class="inline-flex min-h-[44px] items-center gap-2 text-sm">
                    <input v-model="form.allow_registration" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Izinkan pendaftaran akun siswa mandiri di halaman Register</span>
                </label>

                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing" class="min-h-[44px] rounded-[10px] bg-[#1B2CC1] px-6 text-sm font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
