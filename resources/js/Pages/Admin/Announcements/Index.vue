<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Megaphone, Plus, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Announcement, PaginatedData } from '@/types';

defineProps<{
    announcements: PaginatedData<Announcement>;
}>();

const showModal = ref(false);
const editing = ref<Announcement | null>(null);
const deletingItem = ref<Announcement | null>(null);
const isDeleting = ref(false);

const form = useForm({
    title: '',
    content: '',
    is_pinned: false,
});

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(item: Announcement): void {
    editing.value = item;
    form.clearErrors();
    form.title = item.title;
    form.content = item.content;
    form.is_pinned = item.is_pinned;
    showModal.value = true;
}

function submit(): void {
    if (editing.value) {
        form.put(route('admin.announcements.update', editing.value.id), { onSuccess: () => (showModal.value = false) });
    } else {
        form.post(route('admin.announcements.store'), { onSuccess: () => (showModal.value = false) });
    }
}

function remove(item: Announcement): void {
    deletingItem.value = item;
}

function confirmRemove(): void {
    if (!deletingItem.value) return;
    isDeleting.value = true;
    router.delete(route('admin.announcements.destroy', deletingItem.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            deletingItem.value = null;
        },
    });
}
</script>

<template>
    <Head title="Pengumuman Global" />

    <AuthenticatedLayout title="Pengumuman Global">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-[#475569] dark:text-[#CBD5E1]">Pengumuman global dikirim sebagai notifikasi ke seluruh pengguna aktif.</p>
                <button type="button" @click="openCreate" class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    <span>Terbitkan Pengumuman</span>
                </button>
            </div>

            <EmptyState v-if="announcements.data.length === 0" :icon="Megaphone" title="Belum ada pengumuman global" description="Terbitkan informasi penting institusi kepada seluruh pengajar dan siswa." />

            <div v-else class="space-y-3">
                <article v-for="item in announcements.data" :key="item.id" class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <StatusBadge v-if="item.is_pinned" label="Disematkan" variant="mint" />
                                <h3 class="text-base font-bold">{{ item.title }}</h3>
                            </div>
                            <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                                Oleh {{ item.author?.name }} • {{ new Date(item.published_at).toLocaleString('id-ID') }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="openEdit(item)" class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs font-semibold dark:border-[#1B2CC1]">Edit</button>
                            <button type="button" @click="remove(item)" aria-label="Hapus pengumuman" class="inline-flex min-h-[38px] min-w-[38px] items-center justify-center rounded-[8px] border border-red-200 text-red-600 dark:border-red-900 dark:text-red-400">
                                <Trash2 class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed">{{ item.content }}</p>
                </article>
                <PaginationBar :links="announcements.links" :from="announcements.from" :to="announcements.to" :total="announcements.total" />
            </div>
        </div>

        <AppModal :show="showModal" :title="editing ? 'Edit Pengumuman' : 'Terbitkan Pengumuman Global'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="ga-title" class="block text-sm font-semibold">Judul</label>
                    <input id="ga-title" v-model="form.title" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <InputError :message="form.errors.title" />
                </div>
                <div>
                    <label for="ga-content" class="block text-sm font-semibold">Isi Pengumuman</label>
                    <textarea id="ga-content" v-model="form.content" rows="5" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <InputError :message="form.errors.content" />
                </div>
                <label class="inline-flex min-h-[44px] items-center gap-2 text-sm">
                    <input v-model="form.is_pinned" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Sematkan di bagian atas</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="form.processing" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Simpan</button>
                </div>
            </form>
        </AppModal>

        <ConfirmModal
            :show="Boolean(deletingItem)"
            title="Hapus Pengumuman"
            :message="`Hapus pengumuman &quot;${deletingItem?.title ?? ''}&quot;?`"
            confirm-text="Hapus"
            :processing="isDeleting"
            @confirm="confirmRemove"
            @cancel="deletingItem = null"
        />
    </AuthenticatedLayout>
</template>
