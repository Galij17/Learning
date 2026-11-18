<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Award,
    BookOpen,
    ClipboardCheck,
    Copy,
    HelpCircle,
    Megaphone,
    MessageSquare,
    RefreshCw,
    Settings,
    Users,
} from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CourseAnnouncementsTab from '@/Components/Instructor/Course/CourseAnnouncementsTab.vue';
import CourseAssignmentsTab from '@/Components/Instructor/Course/CourseAssignmentsTab.vue';
import CourseCurriculumTab from '@/Components/Instructor/Course/CourseCurriculumTab.vue';
import CourseQuizzesTab from '@/Components/Instructor/Course/CourseQuizzesTab.vue';
import CourseSettingsTab from '@/Components/Instructor/Course/CourseSettingsTab.vue';
import CourseStudentsTab from '@/Components/Instructor/Course/CourseStudentsTab.vue';
import { useToast } from '@/composables/useToast';
import type { Course } from '@/types';

const props = defineProps<{
    course: Course;
    categories: Array<{ id: number; name: string }>;
    subjects: Array<{ id: number; name: string; category_id: number }>;
    periods: Array<{ id: number; name: string; is_active: boolean }>;
    availableStudents: Array<{ id: number; name: string; email: string; identity_number: string | null }>;
}>();

const toast = useToast();
type TabKey = 'curriculum' | 'assignments' | 'quizzes' | 'students' | 'announcements' | 'settings';
const activeTab = ref<TabKey>('curriculum');

const pendingConfirm = ref<{
    title: string;
    message: string;
    confirmText: string;
    danger?: boolean;
    onConfirm: (done: () => void) => void;
} | null>(null);
const isConfirming = ref(false);

async function copyCourseCode(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.course.code);
        toast.success(`Kode kelas ${props.course.code} disalin ke clipboard.`);
    } catch {
        toast.error('Gagal menyalin kode kelas.');
    }
}

function runPendingConfirm(): void {
    if (!pendingConfirm.value) return;
    isConfirming.value = true;
    pendingConfirm.value.onConfirm(() => {
        isConfirming.value = false;
        pendingConfirm.value = null;
    });
}

function regenerateCode(): void {
    pendingConfirm.value = {
        title: 'Generate Ulang Kode Kelas',
        message: 'Generate ulang kode gabung? Kode lama tidak akan berlaku lagi.',
        confirmText: 'Generate Ulang',
        danger: false,
        onConfirm: (done) => {
            router.post(route('instructor.courses.regenerate-code', props.course.id), {}, { preserveScroll: true, onFinish: done });
        },
    };
}

function deleteLesson(lessonId: number): void {
    pendingConfirm.value = {
        title: 'Hapus Bab',
        message: 'Hapus bab ini beserta seluruh materi di dalamnya?',
        confirmText: 'Hapus Bab',
        onConfirm: (done) => {
            router.delete(route('instructor.lessons.destroy', [props.course.id, lessonId]), { preserveScroll: true, onFinish: done });
        },
    };
}

function deleteMaterial(materialId: number): void {
    pendingConfirm.value = {
        title: 'Hapus Materi',
        message: 'Hapus materi ini?',
        confirmText: 'Hapus Materi',
        onConfirm: (done) => {
            router.delete(route('instructor.materials.destroy', [props.course.id, materialId]), { preserveScroll: true, onFinish: done });
        },
    };
}

function deleteAssignment(assignmentId: number): void {
    pendingConfirm.value = {
        title: 'Hapus Tugas',
        message: 'Hapus tugas ini? Data pengumpulan siswa ikut terhapus.',
        confirmText: 'Hapus Tugas',
        onConfirm: (done) => {
            router.delete(route('instructor.assignments.destroy', [props.course.id, assignmentId]), { preserveScroll: true, onFinish: done });
        },
    };
}

function deleteQuiz(quizId: number): void {
    pendingConfirm.value = {
        title: 'Hapus Kuis / Ujian',
        message: 'Hapus kuis/ujian ini?',
        confirmText: 'Hapus',
        onConfirm: (done) => {
            router.delete(route('instructor.quizzes.destroy', [props.course.id, quizId]), { preserveScroll: true, onFinish: done });
        },
    };
}

function removeStudent(studentId: number): void {
    pendingConfirm.value = {
        title: 'Keluarkan Peserta',
        message: 'Keluarkan peserta ini dari kelas?',
        confirmText: 'Keluarkan',
        onConfirm: (done) => {
            router.delete(route('instructor.courses.students.remove', [props.course.id, studentId]), { preserveScroll: true, onFinish: done });
        },
    };
}

function deleteAnnouncement(announcementId: number): void {
    pendingConfirm.value = {
        title: 'Hapus Pengumuman',
        message: 'Hapus pengumuman ini?',
        confirmText: 'Hapus',
        onConfirm: (done) => {
            router.delete(route('instructor.announcements.destroy', [props.course.id, announcementId]), { preserveScroll: true, onFinish: done });
        },
    };
}
</script>

<template>
    <Head :title="course.title" />

    <AuthenticatedLayout :title="course.title">
        <div class="space-y-6">
            <section class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Link
                            :href="route('instructor.courses.index')"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                        >
                            <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                            Kembali ke Kelas Saya
                        </Link>
                        <div class="mt-2 flex flex-wrap items-center gap-2.5">
                            <h2 class="text-xl font-bold">{{ course.title }}</h2>
                            <StatusBadge :label="course.status" :variant="course.status === 'published' ? 'mint' : 'amber'" />
                        </div>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            {{ course.category?.name }} • {{ course.subject?.name || 'Umum' }} • {{ course.academic_period?.name || 'Tanpa Periode' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 rounded-[10px] border border-[#E2E8F0] bg-[#F8FAFC] px-3 py-1.5 dark:border-[#1B2CC1] dark:bg-[#162030]">
                            <span class="text-xs text-[#475569] dark:text-[#CBD5E1]">Kode:</span>
                            <span class="font-mono text-sm font-bold text-[#1E293B] dark:text-[#ABD2FA]">{{ course.code }}</span>
                            <button type="button" @click="copyCourseCode" aria-label="Salin kode kelas" class="rounded p-1.5 hover:bg-[#E2E8F0] dark:hover:bg-[#3E4F69]">
                                <Copy class="h-3.5 w-3.5" aria-hidden="true" />
                            </button>
                            <button type="button" @click="regenerateCode" aria-label="Generate ulang kode kelas" class="rounded p-1.5 hover:bg-[#E2E8F0] dark:hover:bg-[#3E4F69]">
                                <RefreshCw class="h-3.5 w-3.5" aria-hidden="true" />
                            </button>
                        </div>

                        <Link
                            :href="route('instructor.gradebook.index', course.id)"
                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3 text-xs font-semibold text-[#1B2CC1] hover:bg-[#F8FAFC] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69]"
                        >
                            <Award class="h-4 w-4" aria-hidden="true" />
                            Buku Nilai
                        </Link>
                        <Link
                            :href="route('courses.discussions.index', course.id)"
                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3 text-xs font-semibold text-[#1B2CC1] hover:bg-[#F8FAFC] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69]"
                        >
                            <MessageSquare class="h-4 w-4" aria-hidden="true" />
                            Diskusi
                        </Link>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2 border-t border-[#E2E8F0] pt-4 dark:border-[#3E4F69]">
                    <button
                        v-for="tab in [
                            { key: 'curriculum', label: `Bab & Materi (${course.lessons?.length ?? 0})`, icon: BookOpen },
                            { key: 'assignments', label: `Tugas (${course.assignments?.length ?? 0})`, icon: ClipboardCheck },
                            { key: 'quizzes', label: `Kuis & Ujian (${course.quizzes?.length ?? 0})`, icon: HelpCircle },
                            { key: 'students', label: `Peserta (${course.enrollments?.length ?? 0})`, icon: Users },
                            { key: 'announcements', label: `Pengumuman (${course.announcements?.length ?? 0})`, icon: Megaphone },
                            { key: 'settings', label: 'Pengaturan Kelas', icon: Settings },
                        ]"
                        :key="tab.key"
                        type="button"
                        @click="activeTab = tab.key as TabKey"
                        :class="[
                            'inline-flex min-h-[40px] items-center gap-2 rounded-[8px] px-3.5 text-xs font-semibold transition-colors',
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

            <CourseCurriculumTab
                v-if="activeTab === 'curriculum'"
                :course-id="course.id"
                :lessons="course.lessons ?? []"
                @delete-lesson="deleteLesson"
                @delete-material="deleteMaterial"
            />

            <CourseAssignmentsTab
                v-if="activeTab === 'assignments'"
                :course-id="course.id"
                :assignments="course.assignments ?? []"
                @delete-assignment="deleteAssignment"
            />

            <CourseQuizzesTab
                v-if="activeTab === 'quizzes'"
                :course-id="course.id"
                :quizzes="course.quizzes ?? []"
                @delete-quiz="deleteQuiz"
            />

            <CourseStudentsTab
                v-if="activeTab === 'students'"
                :course-id="course.id"
                :enrollments="course.enrollments ?? []"
                :available-students="availableStudents"
                @remove-student="removeStudent"
            />

            <CourseAnnouncementsTab
                v-if="activeTab === 'announcements'"
                :course-id="course.id"
                :announcements="course.announcements ?? []"
                @delete-announcement="deleteAnnouncement"
            />

            <CourseSettingsTab
                v-if="activeTab === 'settings'"
                :course="course"
                :categories="categories"
                :subjects="subjects"
                :periods="periods"
            />
        </div>

        <ConfirmModal
            :show="Boolean(pendingConfirm)"
            :title="pendingConfirm?.title ?? 'Konfirmasi Aksi'"
            :message="pendingConfirm?.message ?? ''"
            :confirm-text="pendingConfirm?.confirmText ?? 'Lanjutkan'"
            :danger="pendingConfirm?.danger ?? true"
            :processing="isConfirming"
            @confirm="runPendingConfirm"
            @cancel="pendingConfirm = null"
        />
    </AuthenticatedLayout>
</template>
