<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Award,
    BookOpen,
    CheckCircle2,
    ClipboardCheck,
    Download,
    FileText,
    HelpCircle,
    Megaphone,
    MessageSquare,
} from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Certificate, Course, Enrollment, Grade } from '@/types';

defineProps<{
    course: Course;
    enrollment: Enrollment | null;
    completedMaterialIds: number[];
    grade: Grade | null;
    certificate: Certificate | null;
}>();

type TabKey = 'curriculum' | 'assignments' | 'quizzes' | 'announcements';
const activeTab = ref<TabKey>('curriculum');

function formatDate(val?: string | null): string {
    if (!val) return '-';
    return new Date(val).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>
    <Head :title="course.title" />

    <AuthenticatedLayout :title="course.title">
        <div class="space-y-6">
            <!-- Course Summary Banner -->
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Link :href="route('student.courses.index')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]">
                            <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                            Kembali ke Kelas Saya
                        </Link>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="rounded-[6px] bg-[#E2E8F0] px-2.5 py-0.5 font-mono text-xs font-bold text-[#1E293B] dark:bg-[#3E4F69] dark:text-[#ABD2FA]">
                                {{ course.code }}
                            </span>
                            <h2 class="text-xl font-bold">{{ course.title }}</h2>
                        </div>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            Pengajar: {{ course.instructor?.name }} • {{ course.category?.name }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="route('courses.discussions.index', course.id)"
                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] hover:bg-[#F8FAFC] dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                        >
                            <MessageSquare class="h-4 w-4" aria-hidden="true" />
                            Forum Diskusi
                        </Link>
                        <Link
                            v-if="certificate"
                            :href="route('certificates.show', certificate.id)"
                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            <Award class="h-4 w-4" aria-hidden="true" />
                            Lihat Sertifikat
                        </Link>
                    </div>
                </div>

                <!-- Progress + Grade Summary -->
                <div class="mt-5 grid gap-4 rounded-[10px] bg-[#F8FAFC] p-4 dark:bg-[#162030] sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Progress Pembelajaran</p>
                        <p class="mt-1 text-lg font-extrabold">{{ Number(enrollment?.progress_percentage ?? 0).toFixed(0) }}%</p>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-[#E2E8F0] dark:bg-[#3E4F69]">
                            <div class="h-full bg-[#7692FF]" :style="{ width: `${Math.min(100, Number(enrollment?.progress_percentage ?? 0))}%` }" />
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">Nilai Akhir Sementara</p>
                        <p class="mt-1 text-lg font-extrabold">
                            {{ grade ? `${grade.final_score} (${grade.letter_grade ?? '-'})` : 'Belum ada nilai' }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end">
                        <a
                            v-if="certificate"
                            :href="route('certificates.download', certificate.id)"
                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                        >
                            <Download class="h-4 w-4" aria-hidden="true" />
                            Unduh PDF Sertifikat
                        </a>
                        <span v-else class="text-xs text-[#475569] dark:text-[#CBD5E1]">
                            Syarat sertifikat: progress >= {{ course.min_completion_percentage }}% & nilai >= {{ course.min_passing_score }}
                        </span>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mt-5 flex flex-wrap gap-2 border-t border-[#E2E8F0] pt-4 dark:border-[#3E4F69]">
                    <button
                        v-for="tab in [
                            { key: 'curriculum', label: `Materi (${course.lessons?.length ?? 0})`, icon: BookOpen },
                            { key: 'assignments', label: `Tugas (${course.assignments?.length ?? 0})`, icon: ClipboardCheck },
                            { key: 'quizzes', label: `Kuis & Ujian (${course.quizzes?.length ?? 0})`, icon: HelpCircle },
                            { key: 'announcements', label: `Pengumuman (${course.announcements?.length ?? 0})`, icon: Megaphone },
                        ]"
                        :key="tab.key"
                        type="button"
                        @click="activeTab = tab.key as TabKey"
                        :class="[
                            'inline-flex min-h-[40px] items-center gap-2 rounded-[8px] px-3.5 text-xs font-semibold',
                            activeTab === tab.key
                                ? 'bg-[#1B2CC1] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]'
                                : 'bg-[#F8FAFC] text-[#475569] hover:bg-[#E2E8F0] dark:bg-[#162030] dark:text-[#CBD5E1] dark:hover:bg-[#3E4F69]',
                        ]"
                    >
                        <component :is="tab.icon" class="h-4 w-4" aria-hidden="true" />
                        <span>{{ tab.label }}</span>
                    </button>
                </div>
            </section>

            <!-- TAB 1: CURRICULUM -->
            <section v-if="activeTab === 'curriculum'" class="space-y-4">
                <EmptyState
                    v-if="!course.lessons || course.lessons.length === 0"
                    :icon="BookOpen"
                    title="Belum ada materi dipublikasikan"
                    description="Pengajar belum mempublikasikan bab pembelajaran pada kelas ini."
                />

                <div v-else class="space-y-4">
                    <article
                        v-for="lesson in course.lessons"
                        :key="lesson.id"
                        class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                    >
                        <span class="rounded-[6px] bg-[#F8FAFC] px-2.5 py-0.5 text-xs font-bold text-[#1B2CC1] dark:bg-[#3E4F69] dark:text-[#ABD2FA]">
                            Pertemuan {{ lesson.meeting_number }}
                        </span>
                        <h3 class="mt-2 text-base font-bold">{{ lesson.title }}</h3>
                        <p v-if="lesson.description" class="mt-1 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ lesson.description }}</p>

                        <div class="mt-4 space-y-2">
                            <p v-if="!lesson.materials || lesson.materials.length === 0" class="text-xs italic text-[#475569]">Belum ada materi di bab ini.</p>
                            <Link
                                v-for="mat in lesson.materials"
                                :key="mat.id"
                                :href="route('student.materials.show', [course.id, mat.id])"
                                class="flex items-center justify-between rounded-[10px] border border-[#E2E8F0] bg-[#F8FAFC] px-4 py-3 transition-colors hover:border-[#7692FF] dark:border-[#3E4F69] dark:bg-[#162030]"
                            >
                                <div class="flex items-center gap-3">
                                    <CheckCircle2
                                        v-if="completedMaterialIds.includes(mat.id)"
                                        class="h-5 w-5 shrink-0 text-[#1B2CC1] dark:text-[#ABD2FA]"
                                        aria-hidden="true"
                                    />
                                    <FileText v-else class="h-5 w-5 shrink-0 text-[#475569] dark:text-[#CBD5E1]" aria-hidden="true" />
                                    <div>
                                        <p class="text-sm font-semibold">{{ mat.title }}</p>
                                        <p class="text-xs uppercase text-[#475569] dark:text-[#CBD5E1]">{{ mat.type }}</p>
                                    </div>
                                </div>
                                <StatusBadge
                                    :label="completedMaterialIds.includes(mat.id) ? 'Selesai' : 'Buka'"
                                    :variant="completedMaterialIds.includes(mat.id) ? 'mint' : 'neutral'"
                                />
                            </Link>
                        </div>
                    </article>
                </div>
            </section>

            <!-- TAB 2: ASSIGNMENTS -->
            <section v-if="activeTab === 'assignments'" class="space-y-4">
                <EmptyState
                    v-if="!course.assignments || course.assignments.length === 0"
                    :icon="ClipboardCheck"
                    title="Belum ada tugas"
                    description="Tugas yang diterbitkan oleh pengajar akan tampil di sini."
                />
                <div v-else class="grid gap-4 md:grid-cols-2">
                    <Link
                        v-for="asg in course.assignments"
                        :key="asg.id"
                        :href="route('student.assignments.show', [course.id, asg.id])"
                        class="block rounded-[14px] border border-[#E2E8F0] bg-white p-5 transition-colors hover:border-[#7692FF] dark:border-[#3E4F69] dark:bg-[#283548]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-base font-bold">{{ asg.title }}</h3>
                            <StatusBadge
                                :label="asg.submissions && asg.submissions[0] ? asg.submissions[0].status : 'Belum dikumpulkan'"
                                :variant="asg.submissions && asg.submissions[0]?.status === 'graded' ? 'mint' : asg.submissions?.length ? 'neutral' : 'amber'"
                            />
                        </div>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">Deadline: {{ formatDate(asg.due_at) }}</p>
                        <p class="mt-2 line-clamp-2 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ asg.description }}</p>
                        <p v-if="asg.submissions?.[0]?.score !== null && asg.submissions?.[0]?.score !== undefined" class="mt-3 text-xs font-bold text-[#1B2CC1] dark:text-[#ABD2FA]">
                            Nilai: {{ asg.submissions[0].score }} / {{ asg.max_score }}
                        </p>
                    </Link>
                </div>
            </section>

            <!-- TAB 3: QUIZZES -->
            <section v-if="activeTab === 'quizzes'" class="space-y-4">
                <EmptyState
                    v-if="!course.quizzes || course.quizzes.length === 0"
                    :icon="HelpCircle"
                    title="Belum ada kuis atau ujian"
                    description="Kuis dan ujian yang tersedia pada kelas ini akan muncul di sini."
                />
                <div v-else class="grid gap-4 md:grid-cols-2">
                    <Link
                        v-for="q in course.quizzes"
                        :key="q.id"
                        :href="route('student.quizzes.show', [course.id, q.id])"
                        class="block rounded-[14px] border border-[#E2E8F0] bg-white p-5 transition-colors hover:border-[#7692FF] dark:border-[#3E4F69] dark:bg-[#283548]"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <StatusBadge :label="q.type === 'exam' ? 'Ujian' : 'Kuis'" variant="pine" />
                            <span class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ q.duration_minutes }} menit • {{ q.questions_count ?? 0 }} soal</span>
                        </div>
                        <h3 class="mt-2.5 text-base font-bold">{{ q.title }}</h3>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            Percobaan Anda: {{ q.attempts?.length ?? 0 }} / {{ q.max_attempts }}
                        </p>
                    </Link>
                </div>
            </section>

            <!-- TAB 4: ANNOUNCEMENTS -->
            <section v-if="activeTab === 'announcements'" class="space-y-3">
                <EmptyState
                    v-if="!course.announcements || course.announcements.length === 0"
                    :icon="Megaphone"
                    title="Belum ada pengumuman"
                    description="Pengumuman khusus kelas ini akan tampil di halaman ini."
                />
                <article
                    v-else
                    v-for="ann in course.announcements"
                    :key="ann.id"
                    class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <h3 class="font-bold">{{ ann.title }}</h3>
                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ ann.author?.name }} • {{ formatDate(ann.published_at) }}</p>
                    <p class="mt-2 whitespace-pre-line text-sm">{{ ann.content }}</p>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
