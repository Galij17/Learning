<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { BookOpen, Download, FileText, Plus, Trash2 } from 'lucide-vue-next';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Lesson } from '@/types';

const props = defineProps<{
    courseId: number;
    lessons: Lesson[];
}>();

const emit = defineEmits<{
    deleteLesson: [lessonId: number];
    deleteMaterial: [materialId: number];
}>();

const showLessonModal = ref(false);
const editingLesson = ref<Lesson | null>(null);
const showMaterialModal = ref(false);

const lessonForm = useForm({
    title: '',
    description: '',
    meeting_number: (props.lessons.length ?? 0) + 1,
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

function openNewLesson(): void {
    editingLesson.value = null;
    lessonForm.reset();
    lessonForm.clearErrors();
    lessonForm.meeting_number = props.lessons.length + 1;
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
        lessonForm.put(route('instructor.lessons.update', [props.courseId, editingLesson.value.id]), {
            onSuccess: () => {
                showLessonModal.value = false;
            },
        });
    } else {
        lessonForm.post(route('instructor.lessons.store', props.courseId), {
            onSuccess: () => {
                showLessonModal.value = false;
                lessonForm.reset();
            },
        });
    }
}

function openMaterialModal(lessonId?: number): void {
    materialForm.reset();
    materialForm.clearErrors();
    materialForm.lesson_id = lessonId ?? props.lessons[0]?.id ?? '';
    materialForm.type = 'pdf';
    materialForm.sort_order = 1;
    materialForm.is_published = true;
    showMaterialModal.value = true;
}

function submitMaterial(): void {
    materialForm.post(route('instructor.materials.store', props.courseId), {
        forceFormData: true,
        onSuccess: () => {
            showMaterialModal.value = false;
            materialForm.reset();
        },
    });
}
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-base font-bold">Struktur Bab / Pertemuan &amp; Materi</h3>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="openNewLesson" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-[8px] bg-[#1B2CC1] px-3.5 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    Tambah Bab
                </button>
                <button
                    v-if="lessons.length > 0"
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
            v-if="lessons.length === 0"
            :icon="BookOpen"
            title="Belum ada bab pembelajaran"
            description="Buat bab atau pertemuan terlebih dahulu agar dapat menambahkan file PDF, PPT, DOCX, gambar, atau video."
        />

        <div v-else class="space-y-4">
            <article
                v-for="lesson in lessons"
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
                        <button type="button" @click="emit('deleteLesson', lesson.id)" aria-label="Hapus bab" class="min-h-[36px] rounded-[8px] border border-[#FECACA] px-2.5 text-[#DC2626] hover:bg-[#FEE2E2] dark:border-[#7F1D1D]">
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
                            <button type="button" @click="emit('deleteMaterial', mat.id)" aria-label="Hapus materi" class="rounded-[6px] p-1.5 text-[#DC2626] hover:bg-[#FEE2E2] dark:hover:bg-[#7F1D1D]/30">
                                <Trash2 class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        </div>

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

        <AppModal :show="showMaterialModal" title="Unggah Materi Pembelajaran" @close="showMaterialModal = false">
            <form @submit.prevent="submitMaterial" class="space-y-4">
                <div>
                    <label for="m_lesson" class="mb-1 block text-xs font-semibold">Pilih Bab</label>
                    <select id="m_lesson" v-model="materialForm.lesson_id" required class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#162030]">
                        <option v-for="l in lessons" :key="l.id" :value="l.id">Pertemuan {{ l.meeting_number }}: {{ l.title }}</option>
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
                    <label for="m_file" class="mb-1 block text-xs font-semibold">Unggah File (Privat &amp; Aman)</label>
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
    </section>
</template>
