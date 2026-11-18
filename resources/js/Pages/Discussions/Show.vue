<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Lock, Pin, Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import InputError from '@/Components/InputError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Course, SharedPageProps } from '@/types';

interface CommentNode {
    id: number;
    user_id: number;
    body: string;
    created_at: string;
    user: { id: number; name: string; roles: Array<{ name: string }> };
    replies?: CommentNode[];
}

const props = defineProps<{
    course: Course;
    discussion: {
        id: number;
        user_id: number;
        title: string;
        body: string;
        is_pinned: boolean;
        is_locked: boolean;
        created_at: string;
        user: { id: number; name: string; roles: Array<{ name: string }> };
        comments: CommentNode[];
    };
}>();

const page = usePage<SharedPageProps>();
const currentUser = computed(() => page.props.auth.user);
const canModerate = computed(() => ['admin', 'instructor'].includes(currentUser.value?.role ?? ''));

const replyToId = ref<number | null>(null);
const deletingCommentId = ref<number | null>(null);
const isDeletingComment = ref(false);
const commentForm = useForm({
    body: '',
    parent_id: null as number | null,
});

function submitComment(): void {
    commentForm.parent_id = replyToId.value;
    commentForm.post(route('courses.discussions.comments.store', [props.course.id, props.discussion.id]), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
            replyToId.value = null;
        },
    });
}

function togglePin(): void {
    router.post(route('courses.discussions.pin', [props.course.id, props.discussion.id]), {}, { preserveScroll: true });
}

function toggleLock(): void {
    router.post(route('courses.discussions.lock', [props.course.id, props.discussion.id]), {}, { preserveScroll: true });
}

function deleteComment(commentId: number): void {
    deletingCommentId.value = commentId;
}

function confirmDeleteComment(): void {
    if (!deletingCommentId.value) return;
    isDeletingComment.value = true;
    router.delete(route('courses.discussions.comments.destroy', [props.course.id, props.discussion.id, deletingCommentId.value]), {
        preserveScroll: true,
        onFinish: () => {
            isDeletingComment.value = false;
            deletingCommentId.value = null;
        },
    });
}
</script>

<template>
    <Head :title="discussion.title" />

    <AuthenticatedLayout :title="course.title">
        <div class="mx-auto max-w-4xl space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="route('courses.discussions.index', course.id)"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] border border-[#E2E8F0] bg-white px-4 text-sm font-semibold dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Semua Topik</span>
                </Link>
                <div v-if="canModerate" class="flex flex-wrap gap-2">
                    <button type="button" @click="togglePin" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-xs font-semibold dark:border-[#1B2CC1] dark:bg-[#283548]">
                        <Pin class="h-3.5 w-3.5" aria-hidden="true" />
                        <span>{{ discussion.is_pinned ? 'Lepas Sematan' : 'Sematkan' }}</span>
                    </button>
                    <button type="button" @click="toggleLock" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-xs font-semibold dark:border-[#1B2CC1] dark:bg-[#283548]">
                        <Lock class="h-3.5 w-3.5" aria-hidden="true" />
                        <span>{{ discussion.is_locked ? 'Buka Diskusi' : 'Kunci Diskusi' }}</span>
                    </button>
                </div>
            </div>

            <article class="rounded-[14px] border border-[#E2E8F0] bg-white p-6 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="flex flex-wrap items-center gap-2">
                    <StatusBadge v-if="discussion.is_pinned" label="Disematkan" variant="mint" />
                    <StatusBadge v-if="discussion.is_locked" label="Dikunci" variant="amber" />
                </div>
                <h2 class="mt-2 text-xl font-bold">{{ discussion.title }}</h2>
                <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                    Oleh {{ discussion.user.name }} • {{ new Date(discussion.created_at).toLocaleString('id-ID') }}
                </p>
                <div class="mt-4 whitespace-pre-line text-sm leading-relaxed">{{ discussion.body }}</div>
            </article>

            <!-- Comments -->
            <section class="space-y-3">
                <h3 class="text-base font-bold">Balasan ({{ discussion.comments.length }})</h3>
                <div v-for="comment in discussion.comments" :key="comment.id" class="rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-sm font-bold">{{ comment.user.name }}</span>
                            <span class="ml-2 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ new Date(comment.created_at).toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="!discussion.is_locked"
                                type="button"
                                @click="replyToId = comment.id"
                                class="text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                            >
                                Balas
                            </button>
                            <button
                                v-if="canModerate || comment.user_id === currentUser?.id"
                                type="button"
                                @click="deleteComment(comment.id)"
                                aria-label="Hapus komentar"
                                class="text-xs text-red-600 hover:underline dark:text-red-400"
                            >
                                <Trash2 class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm">{{ comment.body }}</p>

                    <div v-if="comment.replies && comment.replies.length > 0" class="mt-3 space-y-2 border-l-2 border-[#ABD2FA] pl-4 dark:border-[#1B2CC1]">
                        <div v-for="reply in comment.replies" :key="reply.id" class="rounded-[8px] bg-[#F8FAFC] p-3 text-sm dark:bg-[#1E293B]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold">{{ reply.user.name }}</span>
                                <button
                                    v-if="canModerate || reply.user_id === currentUser?.id"
                                    type="button"
                                    @click="deleteComment(reply.id)"
                                    aria-label="Hapus balasan"
                                    class="text-xs text-red-600 dark:text-red-400"
                                >
                                    Hapus
                                </button>
                            </div>
                            <p class="mt-1 whitespace-pre-line">{{ reply.body }}</p>
                        </div>
                    </div>
                </div>

                <!-- Reply Form -->
                <form
                    v-if="!discussion.is_locked || canModerate"
                    @submit.prevent="submitComment"
                    class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <div v-if="replyToId" class="mb-2 flex items-center justify-between rounded bg-[#E2E8F0] px-3 py-1.5 text-xs dark:bg-[#3E4F69]">
                        <span>Membalas komentar #{{ replyToId }}</span>
                        <button type="button" @click="replyToId = null" class="font-semibold underline">Batal</button>
                    </div>
                    <label for="c-body" class="block text-sm font-semibold">Tulis Balasan</label>
                    <textarea id="c-body" v-model="commentForm.body" rows="3" required placeholder="Tulis tanggapan Anda..." class="mt-1.5 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <InputError :message="commentForm.errors.body" />
                    <button type="submit" :disabled="commentForm.processing" class="mt-3 min-h-[44px] rounded-[10px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        {{ commentForm.processing ? 'Mengirim...' : 'Kirim Balasan' }}
                    </button>
                </form>
            </section>
        </div>

        <ConfirmModal
            :show="Boolean(deletingCommentId)"
            title="Hapus Komentar"
            message="Hapus komentar ini dari diskusi?"
            confirm-text="Hapus"
            :processing="isDeletingComment"
            @confirm="confirmDeleteComment"
            @cancel="deletingCommentId = null"
        />
    </AuthenticatedLayout>
</template>
