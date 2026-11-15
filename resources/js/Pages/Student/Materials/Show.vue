<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Download, ExternalLink, FileText } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Material } from '@/types';

const props = defineProps<{
    course: { id: number; title: string; code: string };
    material: Material;
    isCompleted: boolean;
}>();

const streamUrl = computed(() => route('materials.stream', props.material.id));
const downloadUrl = computed(() => route('materials.download', props.material.id));

const youtubeEmbedUrl = computed(() => {
    const raw = props.material.video_url ?? '';
    if (!raw) return null;
    const match = raw.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{6,})/);
    if (match?.[1]) {
        return `https://www.youtube.com/embed/${match[1]}`;
    }
    return null;
});

function markCompleted(): void {
    router.post(route('student.materials.complete', [props.course.id, props.material.id]));
}
</script>

<template>
    <Head :title="material.title" />

    <AuthenticatedLayout :title="material.title">
        <div class="mx-auto max-w-4xl space-y-6">
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Link :href="route('student.courses.show', course.id)" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                            <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                            Kembali ke {{ course.title }}
                        </Link>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-bold">{{ material.title }}</h2>
                            <StatusBadge :label="material.type.toUpperCase()" variant="mint" />
                            <StatusBadge v-if="isCompleted" label="Sudah Selesai" variant="pine" />
                        </div>
                        <p v-if="material.lesson" class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            Pertemuan {{ material.lesson.meeting_number }}: {{ material.lesson.title }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a
                            v-if="material.file_path"
                            :href="downloadUrl"
                            class="inline-flex min-h-[42px] items-center gap-2 rounded-[8px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] hover:bg-[#F8FAFC] dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                        >
                            <Download class="h-4 w-4" aria-hidden="true" />
                            Unduh File
                        </a>
                        <button
                            v-if="!isCompleted"
                            type="button"
                            @click="markCompleted"
                            class="inline-flex min-h-[42px] items-center gap-2 rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            <CheckCircle2 class="h-4 w-4" aria-hidden="true" />
                            Tandai Materi Selesai
                        </button>
                    </div>
                </div>
            </section>

            <!-- Content Viewer -->
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div v-if="material.type === 'pdf' && material.file_path" class="space-y-3">
                    <iframe :src="streamUrl" :title="material.title" class="h-[640px] w-full rounded-[10px] border border-[#E2E8F0] dark:border-[#3E4F69]" />
                </div>

                <div v-else-if="material.type === 'image' && material.file_path" class="flex justify-center">
                    <img :src="streamUrl" :alt="material.title" class="max-h-[600px] rounded-[10px] object-contain" />
                </div>

                <div v-else-if="material.type === 'video'" class="space-y-4">
                    <video v-if="material.file_path" controls class="w-full rounded-[10px] bg-black">
                        <source :src="streamUrl" :type="material.mime_type || 'video/mp4'" />
                        Browser Anda tidak mendukung pemutar video.
                    </video>
                    <iframe
                        v-else-if="youtubeEmbedUrl"
                        :src="youtubeEmbedUrl"
                        :title="material.title"
                        allowfullscreen
                        class="aspect-video w-full rounded-[10px]"
                    />
                    <a
                        v-else-if="material.video_url"
                        :href="material.video_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <ExternalLink class="h-4 w-4" aria-hidden="true" />
                        Buka Video Pembelajaran
                    </a>
                </div>

                <div v-else-if="material.type === 'link' && material.video_url" class="py-6 text-center">
                    <a
                        :href="material.video_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <ExternalLink class="h-4 w-4" aria-hidden="true" />
                        Buka Tautan Materi Eksternal
                    </a>
                </div>

                <div v-else-if="material.file_path" class="flex flex-col items-center justify-center py-10 text-center">
                    <FileText class="h-12 w-12 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    <p class="mt-3 text-sm font-bold">{{ material.file_name || material.title }}</p>
                    <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Dokumen ini siap diunduh ke perangkat Anda.</p>
                    <a
                        :href="downloadUrl"
                        class="mt-4 inline-flex min-h-[44px] items-center gap-2 rounded-[8px] bg-[#1B2CC1] px-5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <Download class="h-4 w-4" aria-hidden="true" />
                        Unduh Dokumen
                    </a>
                </div>

                <div v-if="material.content" class="prose max-w-none whitespace-pre-line text-sm leading-relaxed text-[#1E293B] dark:text-[#F8FAFC]">
                    {{ material.content }}
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
