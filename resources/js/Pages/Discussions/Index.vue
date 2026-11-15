<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Lock, MessageSquare, Pin, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Course, PaginatedData, SharedPageProps } from '@/types';

interface DiscussionItem {
    id: number;
    title: string;
    body: string;
    is_pinned: boolean;
    is_locked: boolean;
    all_comments_count: number;
    created_at: string;
    user: { id: number; name: string; roles: Array<{ name: string }> };
}

const props = defineProps<{
    course: Course;
    discussions: PaginatedData<DiscussionItem>;
    filters: { search?: string };
}>();

const page = usePage<SharedPageProps>();
const backCourseUrl = computed(() =>
    page.props.auth.user?.role === 'student'
        ? route('student.courses.show', props.course.id)
        : route('instructor.courses.show', props.course.id)
);

const showModal = ref(false);
const search = ref(props.filters.search ?? '');

const form = useForm({
    title: '',
    body: '',
});

function applySearch(): void {
    router.get(route('courses.discussions.index', props.course.id), { search: search.value }, { preserveState: true, replace: true });
}

function submitTopic(): void {
    form.post(route('courses.discussions.store', props.course.id), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Head :title="`Forum Diskusi - ${course.title}`" />

    <AuthenticatedLayout :title="`Forum Diskusi: ${course.code}`">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link
                        :href="backCourseUrl"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] border border-[#E2E8F0] bg-white px-4 text-sm font-semibold dark:border-[#3E4F69] dark:bg-[#283548]"
                    >
                        <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                        <span>Kembali ke Kelas</span>
                    </Link>
                    <div>
                        <h2 class="text-lg font-bold">{{ course.title }}</h2>
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Ruang tanya jawab dan diskusi materi kelas.</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="showModal = true"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    <span>Buat Topik Baru</span>
                </button>
            </div>

            <form @submit.prevent="applySearch" class="flex gap-2">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Cari topik diskusi..."
                    class="w-full max-w-md rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]"
                />
                <button
                    type="submit"
                    class="min-h-[44px] rounded-[8px] border border-[#1B2CC1] px-4 text-sm font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                >
                    Cari
                </button>
            </form>

            <EmptyState
                v-if="discussions.data.length === 0"
                :icon="MessageSquare"
                title="Belum ada topik diskusi"
                description="Mulai pertanyaan atau diskusi terkait materi pertemuan bersama pengajar dan peserta lainnya."
            />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <ul class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                    <li v-for="item in discussions.data" :key="item.id" class="p-4 hover:bg-[#F8FAFC] dark:hover:bg-[#3E4F69]/30">
                        <Link :href="route('courses.discussions.show', [course.id, item.id])" class="block">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <StatusBadge v-if="item.is_pinned" label="Disematkan" variant="mint" />
                                    <StatusBadge v-if="item.is_locked" label="Dikunci" variant="amber" />
                                    <h3 class="text-base font-bold text-[#1E293B] dark:text-[#F8FAFC]">{{ item.title }}</h3>
                                </div>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-[#475569] dark:text-[#CBD5E1]">
                                    <MessageSquare class="h-3.5 w-3.5" aria-hidden="true" />
                                    {{ item.all_comments_count }} balasan
                                </span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ item.body }}</p>
                            <p class="mt-2 text-xs text-[#475569] dark:text-[#CBD5E1]">
                                Oleh {{ item.user.name }} • {{ new Date(item.created_at).toLocaleDateString('id-ID') }}
                            </p>
                        </Link>
                    </li>
                </ul>
                <PaginationBar :links="discussions.links" :from="discussions.from" :to="discussions.to" :total="discussions.total" />
            </div>
        </div>

        <AppModal :show="showModal" title="Buat Topik Diskusi Baru" @close="showModal = false">
            <form @submit.prevent="submitTopic" class="space-y-4">
                <div>
                    <label for="d-title" class="block text-sm font-semibold">Judul Topik</label>
                    <input id="d-title" v-model="form.title" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label for="d-body" class="block text-sm font-semibold">Isi Pertanyaan / Diskusi</label>
                    <textarea id="d-body" v-model="form.body" rows="5" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <p v-if="form.errors.body" class="mt-1 text-xs text-red-600">{{ form.errors.body }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="form.processing" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Kirim Topik</button>
                </div>
            </form>
        </AppModal>
    </AuthenticatedLayout>
</template>
