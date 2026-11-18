<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Award,
    BookOpen,
    ClipboardCheck,
    Copy,
    Download,
    FileText,
    HelpCircle,
    Megaphone,
    MessageSquare,
    Plus,
    RefreshCw,
    Settings,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useToast } from '@/composables/useToast';
import { formatDate } from '@/utils/format';
import type { Course, Lesson } from '@/types';

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

// Modals
const showLessonModal = ref(false);
const editingLesson = ref<Lesson | null>(null);
const showMaterialModal = ref(false);
const showAssignmentModal = ref(false);
const showQuizModal = ref(false);
const showAnnouncementModal = ref(false);
const showEnrollModal = ref(false);

// Forms
const lessonForm = useForm({
    title: '',
    description: '',
    meeting_number: (props.course.lessons?.length ?? 0) + 1,
    is_published: true,
});

const materialForm = useForm({
    lesson_id: '' as number | string,
    title: '',
    type: 'pdf' as 'pdf' | 'ppt' | 'docx' | 'image' | 'video' | 'link' | 'text',
    file: null as File | null,
    video_url: '',
    content: '',
    sort_order: 1,
    is_published: true,
});

const assignmentForm = useForm({
    lesson_id: '' as number | string,
    title: '',
    description: '',
    attachment: null as File | null,
    due_at: '',
    max_score: 100,
    submission_type: 'both' as 'both' | 'file' | 'text',
    allow_late_submission: false,
    is_published: true,
});

const quizForm = useForm({
    lesson_id: '' as number | string,
    title: '',
    description: '',
    type: 'quiz' as 'quiz' | 'exam',
    duration_minutes: 30,
    starts_at: '',
    ends_at: '',
    max_attempts: 2,
    passing_score: 70,
    shuffle_questions: true,
    shuffle_options: true,
    show_results: true,
    show_explanations: true,
    is_published: true,
});

const announcementForm = useForm({
    title: '',
    content: '',
    is_pinned: false,
});

const enrollForm = useForm({
    student_id: '' as number | string,
});

const settingsForm = useForm({
    title: props.course.title,
    category_id: props.course.category_id,
    subject_id: props.course.subject_id ?? ('' as number | string),
    academic_period_id: props.course.academic_period_id ?? ('' as number | string),
    description: props.course.description ?? '',
    status: props.course.status,
    assignment_weight: props.course.assignment_weight,
    quiz_weight: props.course.quiz_weight,
    exam_weight: props.course.exam_weight,
    min_completion_percentage: props.course.min_completion_percentage,
    min_passing_score: Number(props.course.min_passing_score),
    certificate_enabled: props.course.certificate_enabled,
    thumbnail: null as File | null,
});

const filteredSubjects = computed(() =>
    props.subjects.filter((s) => !settingsForm.category_id || s.category_id === Number(settingsForm.category_id))
);

const weightTotal = computed(
    () => Number(settingsForm.assignment_weight) + Number(settingsForm.quiz_weight) + Number(settingsForm.exam_weight)
);

async function copyCourseCode(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.course.code);
        toast.success(`Kode kelas ${props.course.code} disalin ke clipboard.`);
    } catch {
        toast.error('Gagal menyalin kode kelas.');
    }
}

const pendingConfirm = ref<{
    title: string;
    message: string;
    confirmText: string;
    danger?: boolean;
    onConfirm: (done: () => void) => void;
} | null>(null);
const isConfirming = ref(false);

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

function openNewLesson(): void {
    editingLesson.value = null;
    lessonForm.reset();
    lessonForm.clearErrors();
    lessonForm.meeting_number = (props.course.lessons?.length ?? 0) + 1;
    lessonForm.is_published = true;
    showLessonModal.value = true;
}

function openEditLesson(lesson: Lesson): void {
    editingLesson.value = lesson;
    lessonForm.clearErrors();
    lessonForm.title = lesson.title;
    lessonForm.description = lesson.description ?? '';
    lessonForm.meeting_number = lesson.meeting_number;
    lessonForm.is_published = lesson.is_published;
    showLessonModal.value = true;
}

function submitLesson(): void {
    if (editingLesson.value) {
        lessonForm.put(route('instructor.lessons.update', [props.course.id, editingLesson.value.id]), {
            onSuccess: () => {
                showLessonModal.value = false;
            },
        });
    } else {
        lessonForm.post(route('instructor.lessons.store', props.course.id), {
            onSuccess: () => {
                showLessonModal.value = false;
                lessonForm.reset();
            },
        });
    }
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

function openMaterialModal(lessonId?: number): void {
    materialForm.reset();
    materialForm.clearErrors();
    materialForm.lesson_id = lessonId ?? props.course.lessons?.[0]?.id ?? '';
    materialForm.type = 'pdf';
    materialForm.sort_order = 1;
    materialForm.is_published = true;
    showMaterialModal.value = true;
}

function submitMaterial(): void {
    materialForm.post(route('instructor.materials.store', props.course.id), {
        forceFormData: true,
        onSuccess: () => {
            showMaterialModal.value = false;
            materialForm.reset();
        },
    });
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

function submitAssignment(): void {
    assignmentForm.post(route('instructor.assignments.store', props.course.id), {
        forceFormData: true,
        onSuccess: () => {
            showAssignmentModal.value = false;
            assignmentForm.reset();
        },
    });
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

function submitQuiz(): void {
    quizForm.post(route('instructor.quizzes.store', props.course.id), {
        onSuccess: () => {
            showQuizModal.value = false;
            quizForm.reset();
        },
    });
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

function submitEnroll(): void {
    enrollForm.post(route('instructor.courses.students.enroll', props.course.id), {
        onSuccess: () => {
            showEnrollModal.value = false;
            enrollForm.reset();
        },
    });
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

function submitAnnouncement(): void {
    announcementForm.post(route('instructor.announcements.store', props.course.id), {
        onSuccess: () => {
            showAnnouncementModal.value = false;
            announcementForm.reset();
        },
    });
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

function submitSettings(): void {
    settingsForm.post(route('instructor.courses.update', props.course.id), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head :title="course.title" />

    <AuthenticatedLayout :title="course.title">
        <div class="space-y-6">
            <!-- Header Card -->
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

                <!-- Tabs -->
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

            <!-- TAB 1: CURRICULUM -->
            <section v-if="activeTab === 'curriculum'" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-base font-bold">Struktur Bab / Pertemuan & Materi</h3>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="openNewLesson" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                            <Plus class="h-4 w-4" aria-hidden="true" />
                            Tambah Bab
                        </button>
                        <button
                            v-if="(course.lessons?.length ?? 0) > 0"
                            type="button"
                            @click="openMaterialModal()"
                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                        >
                            <Plus class="h-4 w-4" aria-hidden="true" />
                            Unggah Materi
                        </button>
                    </div>
                </div>

                <EmptyState
                    v-if="!course.lessons || course.lessons.length === 0"
                    :icon="BookOpen"
                    title="Belum ada bab pembelajaran"
                    description="Buat bab atau pertemuan terlebih dahulu agar dapat menambahkan file PDF, PPT, DOCX, gambar, atau video."
                />

                <div v-else class="space-y-4">
                    <article
                        v-for="lesson in course.lessons"
                        :key="lesson.id"
                        class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-[6px] bg-[#F8FAFC] px-2.5 py-0.5 text-xs font-bold text-[#1B2CC1] dark:bg-[#3E4F69] dark:text-[#ABD2FA]">
                                        Pertemuan {{ lesson.meeting_number }}
                                    </span>
                                    <StatusBadge :label="lesson.is_published ? 'Publik' : 'Draft'" :variant="lesson.is_published ? 'mint' : 'amber'" />
                                </div>
                                <h4 class="mt-2 text-base font-bold">{{ lesson.title }}</h4>
                                <p v-if="lesson.description" class="mt-1 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ lesson.description }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="openMaterialModal(lesson.id)" class="min-h-[36px] rounded-[8px] border border-[#1B2CC1] px-3 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
                                    + Materi
                                </button>
                                <button type="button" @click="openEditLesson(lesson)" class="min-h-[36px] rounded-[8px] border border-[#E2E8F0] px-3 text-xs font-semibold dark:border-[#1B2CC1]">
                                    Edit
                                </button>
                                <button type="button" @click="deleteLesson(lesson.id)" aria-label="Hapus bab" class="min-h-[36px] rounded-[8px] border border-[#FECACA] px-2.5 text-[#DC2626] hover:bg-[#FEE2E2] dark:border-[#7F1D1D]">
                                    <Trash2 class="h-4 w-4" aria-hidden="true" />
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2">
                            <p v-if="!lesson.materials || lesson.materials.length === 0" class="text-xs italic text-[#475569] dark:text-[#CBD5E1]">
                                Belum ada materi di bab ini.
                            </p>
                            <div
                                v-for="mat in lesson.materials"
                                :key="mat.id"
                                class="flex flex-wrap items-center justify-between gap-2 rounded-[10px] border border-[#E2E8F0] bg-[#F8FAFC] px-3.5 py-2.5 dark:border-[#3E4F69] dark:bg-[#162030]"
                            >
                                <div class="flex items-center gap-3">
                                    <FileText class="h-4 w-4 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                                    <div>
                                        <p class="text-sm font-semibold">{{ mat.title }}</p>
                                        <p class="text-xs uppercase text-[#475569] dark:text-[#CBD5E1]">
                                            {{ mat.type }} <span v-if="mat.file_name">• {{ mat.file_name }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a
                                        v-if="mat.file_path"
                                        :href="route('materials.download', mat.id)"
                                        class="inline-flex min-h-[34px] items-center gap-1 rounded-[6px] border border-[#E2E8F0] bg-white px-2.5 text-xs font-semibold dark:border-[#1B2CC1] dark:bg-[#283548]"
                                    >
                                        <Download class="h-3.5 w-3.5" aria-hidden="true" />
                                        Unduh
                                    </a>
                                    <button type="button" @click="deleteMaterial(mat.id)" aria-label="Hapus materi" class="rounded-[6px] p-1.5 text-[#DC2626] hover:bg-[#FEE2E2] dark:hover:bg-[#7F1D1D]/30">
                                        <Trash2 class="h-4 w-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- TAB 2: ASSIGNMENTS -->
            <section v-if="activeTab === 'assignments'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold">Daftar Tugas Kelas</h3>
                    <button type="button" @click="showAssignmentModal = true" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        <Plus class="h-4 w-4" aria-hidden="true" />
                        Buat Tugas
                    </button>
                </div>

                <EmptyState v-if="!course.assignments || course.assignments.length === 0" :icon="ClipboardCheck" title="Belum ada tugas" description="Buat tugas baru lengkap dengan deadline, lampiran, dan batas nilai." />

                <div v-else class="grid gap-4 md:grid-cols-2">
                    <article
                        v-for="item in course.assignments"
                        :key="item.id"
                        class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h4 class="text-base font-bold">{{ item.title }}</h4>
                                <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                                    Deadline: {{ formatDate(item.due_at) }} • Maks: {{ item.max_score }} poin
                                </p>
                            </div>
                            <StatusBadge :label="`${item.submissions_count ?? 0} dikumpulkan`" variant="neutral" />
                        </div>
                        <p class="mt-3 line-clamp-2 text-sm text-[#475569] dark:text-[#CBD5E1]">{{ item.description }}</p>
                        <div class="mt-4 flex items-center justify-between border-t border-[#E2E8F0] pt-3 dark:border-[#3E4F69]">
                            <Link :href="route('instructor.assignments.show', [course.id, item.id])" class="inline-flex min-h-[38px] items-center rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                                Periksa & Beri Nilai
                            </Link>
                            <button type="button" @click="deleteAssignment(item.id)" aria-label="Hapus tugas" class="rounded-[8px] p-2 text-[#DC2626] hover:bg-[#FEE2E2]">
                                <Trash2 class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <!-- TAB 3: QUIZZES & EXAMS -->
            <section v-if="activeTab === 'quizzes'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold">Daftar Kuis & Ujian</h3>
                    <div class="flex gap-2">
                        <Link :href="route('instructor.question-bank.index')" class="inline-flex min-h-[40px] items-center rounded-[8px] border border-[#1B2CC1] px-3.5 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
                            Buka Bank Soal
                        </Link>
                        <Link :href="route('instructor.quizzes.create', course.id)" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                            <Plus class="h-4 w-4" aria-hidden="true" />
                            Buat Kuis / Ujian
                        </Link>
                    </div>
                </div>

                <EmptyState v-if="!course.quizzes || course.quizzes.length === 0" :icon="HelpCircle" title="Belum ada kuis atau ujian" description="Buat kuis atau ujian baru lalu tambahkan soal dari bank soal atau buat langsung." />

                <div v-else class="grid gap-4 md:grid-cols-2">
                    <article
                        v-for="quiz in course.quizzes"
                        :key="quiz.id"
                        class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <StatusBadge :label="quiz.type === 'exam' ? 'Ujian' : 'Kuis'" :variant="quiz.type === 'exam' ? 'pine' : 'mint'" />
                                    <StatusBadge :label="quiz.is_published ? 'Publik' : 'Draft'" :variant="quiz.is_published ? 'mint' : 'amber'" />
                                </div>
                                <h4 class="mt-2 text-base font-bold">{{ quiz.title }}</h4>
                                <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                                    {{ quiz.duration_minutes }} menit • {{ quiz.questions_count ?? 0 }} soal • {{ quiz.attempts_count ?? 0 }} percobaan • KKM {{ quiz.passing_score }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between border-t border-[#E2E8F0] pt-3 dark:border-[#3E4F69]">
                            <Link :href="route('instructor.quizzes.show', [course.id, quiz.id])" class="inline-flex min-h-[38px] items-center rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                                Kelola Soal & Nilai Essay
                            </Link>
                            <button type="button" @click="deleteQuiz(quiz.id)" aria-label="Hapus kuis" class="rounded-[8px] p-2 text-[#DC2626] hover:bg-[#FEE2E2]">
                                <Trash2 class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <!-- TAB 4: STUDENTS -->
            <section v-if="activeTab === 'students'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold">Peserta Terdaftar</h3>
                    <button type="button" @click="showEnrollModal = true" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        <UserPlus class="h-4 w-4" aria-hidden="true" />
                        Tambah Peserta Manual
                    </button>
                </div>

                <div class="overflow-x-auto rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#162030] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3">Peserta</th>
                                <th class="px-4 py-3">NIM / ID</th>
                                <th class="px-4 py-3">Progress</th>
                                <th class="px-4 py-3">Nilai Akhir</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-if="!course.enrollments || course.enrollments.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-[#475569] dark:text-[#CBD5E1]">Belum ada peserta di kelas ini.</td>
                            </tr>
                            <tr v-for="enr in course.enrollments" :key="enr.id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ enr.student?.name }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ enr.student?.email }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">{{ enr.student?.identity_number || '-' }}</td>
                                <td class="px-4 py-3">{{ Number(enr.progress_percentage).toFixed(0) }}%</td>
                                <td class="px-4 py-3 font-bold">{{ enr.final_score ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" @click="removeStudent(enr.student_id)" class="text-xs font-semibold text-[#DC2626] hover:underline">
                                        Keluarkan
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- TAB 5: ANNOUNCEMENTS -->
            <section v-if="activeTab === 'announcements'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold">Pengumuman Kelas</h3>
                    <button type="button" @click="showAnnouncementModal = true" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        <Plus class="h-4 w-4" aria-hidden="true" />
                        Buat Pengumuman
                    </button>
                </div>

                <EmptyState v-if="!course.announcements || course.announcements.length === 0" :icon="Megaphone" title="Belum ada pengumuman kelas" description="Kirim pengumuman penting agar seluruh peserta kelas menerima notifikasi." />

                <div v-else class="space-y-3">
                    <article
                        v-for="ann in course.announcements"
                        :key="ann.id"
                        class="rounded-[14px] border border-[#E2E8F0] bg-white p-4 dark:border-[#3E4F69] dark:bg-[#283548]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h4 class="font-bold">{{ ann.title }}</h4>
                                <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ ann.author?.name }} • {{ formatDate(ann.published_at) }}</p>
                            </div>
                            <button type="button" @click="deleteAnnouncement(ann.id)" aria-label="Hapus pengumuman" class="rounded p-1.5 text-[#DC2626] hover:bg-[#FEE2E2]">
                                <Trash2 class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm">{{ ann.content }}</p>
                    </article>
                </div>
            </section>

            <!-- TAB 6: SETTINGS -->
            <section v-if="activeTab === 'settings'" class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
                <h3 class="text-base font-bold">Pengaturan Kelas & Bobot Penilaian</h3>
                <form @submit.prevent="submitSettings" class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="s_title" class="mb-1 block text-xs font-semibold">Judul Kelas</label>
                            <input id="s_title" v-model="settingsForm.title" type="text" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        </div>
                        <div>
                            <label for="s_status" class="mb-1 block text-xs font-semibold">Status Publikasi</label>
                            <select id="s_status" v-model="settingsForm.status" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="s_cat" class="mb-1 block text-xs font-semibold">Kategori</label>
                            <select id="s_cat" v-model="settingsForm.category_id" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="s_subj" class="mb-1 block text-xs font-semibold">Mata Pelajaran</label>
                            <select id="s_subj" v-model="settingsForm.subject_id" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                                <option value="">Pilih Mata Pelajaran</option>
                                <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="s_period" class="mb-1 block text-xs font-semibold">Periode Akademik</label>
                            <select id="s_period" v-model="settingsForm.academic_period_id" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                                <option value="">Tanpa Periode</option>
                                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="s_desc" class="mb-1 block text-xs font-semibold">Deskripsi Kelas</label>
                        <textarea id="s_desc" v-model="settingsForm.description" rows="3" class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="s_aw" class="mb-1 block text-xs font-semibold">Bobot Tugas (%)</label>
                            <input id="s_aw" v-model.number="settingsForm.assignment_weight" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        </div>
                        <div>
                            <label for="s_qw" class="mb-1 block text-xs font-semibold">Bobot Kuis (%)</label>
                            <input id="s_qw" v-model.number="settingsForm.quiz_weight" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        </div>
                        <div>
                            <label for="s_ew" class="mb-1 block text-xs font-semibold">Bobot Ujian (%)</label>
                            <input id="s_ew" v-model.number="settingsForm.exam_weight" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        </div>
                    </div>
                    <p v-if="weightTotal !== 100" class="text-xs font-semibold text-[#DC2626]">Total bobot harus 100% (saat ini {{ weightTotal }}%).</p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="s_comp" class="mb-1 block text-xs font-semibold">Syarat Progress Sertifikat (%)</label>
                            <input id="s_comp" v-model.number="settingsForm.min_completion_percentage" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        </div>
                        <div>
                            <label for="s_pass" class="mb-1 block text-xs font-semibold">Nilai Kelulusan Minimum</label>
                            <input id="s_pass" v-model.number="settingsForm.min_passing_score" type="number" min="0" max="100" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="settingsForm.certificate_enabled" type="checkbox" class="h-4 w-4 rounded" />
                        <span>Terbitkan sertifikat otomatis bila siswa memenuhi syarat</span>
                    </label>

                    <button type="submit" :disabled="settingsForm.processing || weightTotal !== 100" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        Simpan Perubahan Kelas
                    </button>
                </form>
            </section>
        </div>

        <!-- MODAL: LESSON -->
        <AppModal :show="showLessonModal" :title="editingLesson ? 'Edit Bab' : 'Tambah Bab / Pertemuan'" @close="showLessonModal = false">
            <form @submit.prevent="submitLesson" class="space-y-4">
                <div>
                    <label for="l_title" class="mb-1 block text-xs font-semibold">Judul Bab</label>
                    <input id="l_title" v-model="lessonForm.title" type="text" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    <InputError :message="lessonForm.errors.title" />
                </div>
                <div>
                    <label for="l_num" class="mb-1 block text-xs font-semibold">Nomor Pertemuan</label>
                    <input id="l_num" v-model.number="lessonForm.meeting_number" type="number" min="1" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    <InputError :message="lessonForm.errors.meeting_number" />
                </div>
                <div>
                    <label for="l_desc" class="mb-1 block text-xs font-semibold">Ringkasan</label>
                    <textarea id="l_desc" v-model="lessonForm.description" rows="2" class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                    <InputError :message="lessonForm.errors.description" />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="lessonForm.is_published" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Publikasikan ke siswa</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showLessonModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="lessonForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Simpan</button>
                </div>
            </form>
        </AppModal>

        <!-- MODAL: MATERIAL -->
        <AppModal :show="showMaterialModal" title="Unggah Materi Pembelajaran" @close="showMaterialModal = false">
            <form @submit.prevent="submitMaterial" class="space-y-4">
                <div>
                    <label for="m_lesson" class="mb-1 block text-xs font-semibold">Pilih Bab</label>
                    <select id="m_lesson" v-model="materialForm.lesson_id" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option v-for="l in course.lessons" :key="l.id" :value="l.id">Pertemuan {{ l.meeting_number }}: {{ l.title }}</option>
                    </select>
                    <InputError :message="materialForm.errors.lesson_id" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="m_title" class="mb-1 block text-xs font-semibold">Judul Materi</label>
                        <input id="m_title" v-model="materialForm.title" type="text" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="materialForm.errors.title" />
                    </div>
                    <div>
                        <label for="m_type" class="mb-1 block text-xs font-semibold">Tipe Materi</label>
                        <select id="m_type" v-model="materialForm.type" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                            <option value="pdf">PDF</option>
                            <option value="ppt">PPT / Presentasi</option>
                            <option value="docx">DOCX / Dokumen</option>
                            <option value="image">Gambar</option>
                            <option value="video">Video</option>
                            <option value="link">Tautan Eksternal</option>
                            <option value="text">Artikel Teks</option>
                        </select>
                        <InputError :message="materialForm.errors.type" />
                    </div>
                </div>
                <div v-if="['pdf', 'ppt', 'docx', 'image', 'video'].includes(materialForm.type)">
                    <label for="m_file" class="mb-1 block text-xs font-semibold">Unggah File (Privat & Aman)</label>
                    <input id="m_file" type="file" @change="materialForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null" class="w-full text-xs" />
                    <InputError :message="materialForm.errors.file" />
                </div>
                <div v-if="['video', 'link'].includes(materialForm.type)">
                    <label for="m_url" class="mb-1 block text-xs font-semibold">URL Video / Tautan</label>
                    <input id="m_url" v-model="materialForm.video_url" type="url" placeholder="https://..." class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    <InputError :message="materialForm.errors.video_url" />
                </div>
                <div v-if="materialForm.type === 'text'">
                    <label for="m_content" class="mb-1 block text-xs font-semibold">Konten Teks</label>
                    <textarea id="m_content" v-model="materialForm.content" rows="4" class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                    <InputError :message="materialForm.errors.content" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showMaterialModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="materialForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Unggah</button>
                </div>
            </form>
        </AppModal>

        <!-- MODAL: ASSIGNMENT -->
        <AppModal :show="showAssignmentModal" title="Buat Tugas Baru" @close="showAssignmentModal = false">
            <form @submit.prevent="submitAssignment" class="space-y-4">
                <div>
                    <label for="a_title" class="mb-1 block text-xs font-semibold">Judul Tugas</label>
                    <input id="a_title" v-model="assignmentForm.title" type="text" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                    <InputError :message="assignmentForm.errors.title" />
                </div>
                <div>
                    <label for="a_desc" class="mb-1 block text-xs font-semibold">Instruksi Tugas</label>
                    <textarea id="a_desc" v-model="assignmentForm.description" rows="3" required class="w-full rounded-[8px] border border-[#E2E8F0] p-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]"></textarea>
                    <InputError :message="assignmentForm.errors.description" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="a_due" class="mb-1 block text-xs font-semibold">Batas Waktu</label>
                        <input id="a_due" v-model="assignmentForm.due_at" type="datetime-local" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="assignmentForm.errors.due_at" />
                    </div>
                    <div>
                        <label for="a_max" class="mb-1 block text-xs font-semibold">Nilai Maksimal</label>
                        <input id="a_max" v-model.number="assignmentForm.max_score" type="number" min="1" max="1000" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="assignmentForm.errors.max_score" />
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="a_stype" class="mb-1 block text-xs font-semibold">Tipe Pengumpulan</label>
                        <select id="a_stype" v-model="assignmentForm.submission_type" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                            <option value="both">File & Teks</option>
                            <option value="file">Hanya File</option>
                            <option value="text">Hanya Teks</option>
                        </select>
                        <InputError :message="assignmentForm.errors.submission_type" />
                    </div>
                    <div>
                        <label for="a_att" class="mb-1 block text-xs font-semibold">Lampiran Soal (Opsional)</label>
                        <input id="a_att" type="file" @change="assignmentForm.attachment = ($event.target as HTMLInputElement).files?.[0] ?? null" class="w-full text-xs" />
                        <InputError :message="assignmentForm.errors.attachment" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="assignmentForm.allow_late_submission" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Izinkan pengumpulan terlambat</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAssignmentModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="assignmentForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Terbitkan Tugas</button>
                </div>
            </form>
        </AppModal>

        <!-- MODAL: QUIZ -->
        <AppModal :show="showQuizModal" title="Buat Kuis / Ujian Baru" @close="showQuizModal = false">
            <form @submit.prevent="submitQuiz" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="q_title" class="mb-1 block text-xs font-semibold">Judul Kuis / Ujian</label>
                        <input id="q_title" v-model="quizForm.title" type="text" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="quizForm.errors.title" />
                    </div>
                    <div>
                        <label for="q_type" class="mb-1 block text-xs font-semibold">Jenis Evaluasi</label>
                        <select id="q_type" v-model="quizForm.type" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                            <option value="quiz">Kuis</option>
                            <option value="exam">Ujian</option>
                        </select>
                        <InputError :message="quizForm.errors.type" />
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label for="q_dur" class="mb-1 block text-xs font-semibold">Durasi (menit)</label>
                        <input id="q_dur" v-model.number="quizForm.duration_minutes" type="number" min="1" max="360" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="quizForm.errors.duration_minutes" />
                    </div>
                    <div>
                        <label for="q_att" class="mb-1 block text-xs font-semibold">Maks Percobaan</label>
                        <input id="q_att" v-model.number="quizForm.max_attempts" type="number" min="1" max="20" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="quizForm.errors.max_attempts" />
                    </div>
                    <div>
                        <label for="q_pass" class="mb-1 block text-xs font-semibold">KKM</label>
                        <input id="q_pass" v-model.number="quizForm.passing_score" type="number" min="0" max="100" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="quizForm.errors.passing_score" />
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="q_start" class="mb-1 block text-xs font-semibold">Mulai (Opsional)</label>
                        <input id="q_start" v-model="quizForm.starts_at" type="datetime-local" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="quizForm.errors.starts_at" />
                    </div>
                    <div>
                        <label for="q_end" class="mb-1 block text-xs font-semibold">Selesai (Opsional)</label>
                        <input id="q_end" v-model="quizForm.ends_at" type="datetime-local" class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]" />
                        <InputError :message="quizForm.errors.ends_at" />
                    </div>
                </div>
                <div class="grid gap-2 sm:grid-cols-2 text-xs">
                    <label class="flex items-center gap-2"><input v-model="quizForm.shuffle_questions" type="checkbox" class="h-4 w-4 rounded" /> Acak urutan soal</label>
                    <label class="flex items-center gap-2"><input v-model="quizForm.shuffle_options" type="checkbox" class="h-4 w-4 rounded" /> Acak pilihan jawaban</label>
                    <label class="flex items-center gap-2"><input v-model="quizForm.show_results" type="checkbox" class="h-4 w-4 rounded" /> Tampilkan hasil ke siswa</label>
                    <label class="flex items-center gap-2"><input v-model="quizForm.show_explanations" type="checkbox" class="h-4 w-4 rounded" /> Tampilkan pembahasan</label>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showQuizModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="quizForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Buat & Pilih Soal</button>
                </div>
            </form>
        </AppModal>

        <!-- MODAL: ENROLL STUDENT -->
        <AppModal :show="showEnrollModal" title="Tambahkan Peserta ke Kelas" @close="showEnrollModal = false">
            <form @submit.prevent="submitEnroll" class="space-y-4">
                <div>
                    <label for="e_stu" class="mb-1 block text-xs font-semibold">Pilih Mahasiswa / Siswa</label>
                    <select id="e_stu" v-model="enrollForm.student_id" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option value="">Pilih peserta...</option>
                        <option v-for="s in availableStudents" :key="s.id" :value="s.id">
                            {{ s.name }} ({{ s.identity_number || s.email }})
                        </option>
                    </select>
                    <InputError :message="enrollForm.errors.student_id" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showEnrollModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="enrollForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Daftarkan</button>
                </div>
            </form>
        </AppModal>

        <!-- MODAL: ANNOUNCEMENT -->
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
