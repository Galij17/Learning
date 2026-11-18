<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Megaphone, Plus, Trash2 } from 'lucide-vue-next';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import { formatDate } from '@/utils/format';
import type { Announcement } from '@/types';

const props = defineProps<{
    courseId: number;
    announcements: Announcement[];
}>();

const emit = defineEmits<{
    deleteAnnouncement: [announcementId: number];
}>();

const showAnnouncementModal = ref(false);

const announcementForm = useForm({
    title: '',
    content: '',
    is_pinned: false,
});

function submitAnnouncement(): void {
    announcementForm.post(route('instructor.announcements.store', props.courseId), {
        onSuccess: () => {
            showAnnouncementModal.value = false;
            announcementForm.reset();
        },
    });
}
</script>

<template>
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold">Pengumuman Kelas</h3>
            <button type="button" @click="showAnnouncementModal = true" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                <Plus class="h-4 w-4" aria-hidden="true" />
                Buat Pengumuman
            </button>
        </div>

        <EmptyState
            v-if="announcements.length === 0"
            :icon="Megaphone"
            title="Belum ada pengumuman kelas"
            description="Kirim pengumuman penting agar seluruh peserta kelas menerima notifikasi."
        />

        <div v-else class="space-y-3">
            <article
                v-for="ann in announcements"
                :key="ann.id"
                class="rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h4 class="font-bold">{{ ann.title }}</h4>
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ ann.author?.name }} • {{ formatDate(ann.published_at) }}</p>
                    </div>
                    <button type="button" @click="emit('deleteAnnouncement', ann.id)" aria-label="Hapus pengumuman" class="rounded p-1.5 text-[#DC2626] hover:bg-[#FEE2E2]">
                        <Trash2 class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
                <p class="mt-2 whitespace-pre-line text-sm">{{ ann.content }}</p>
            </article>
        </div>

        <AppModal :show="showAnnouncementModal" title="Buat Pengumuman Kelas" @close="showAnnouncementModal = false">
            <form @submit.prevent="submitAnnouncement" class="space-y-4">
                <div>
                    <label for="an_title" class="mb-1 block text-xs font-semibold">Judul Pengumuman</label>
                    <input id="an_title" v-model="announcementForm.title" type="text" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    <InputError :message="announcementForm.errors.title" />
                </div>
                <div>
                    <label for="an_body" class="mb-1 block text-xs font-semibold">Isi Pengumuman</label>
                    <textarea id="an_body" v-model="announcementForm.content" rows="4" required class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                    <InputError :message="announcementForm.errors.content" />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="announcementForm.is_pinned" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Sematkan pengumuman di atas</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAnnouncementModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="announcementForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Kirim Pengumuman</button>
                </div>
            </form>
        </AppModal>
    </section>
</template>
