<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Download, FileSpreadsheet, Loader2, Upload } from 'lucide-vue-next';
import AppModal from '@/Components/AppModal.vue';
import InputError from '@/Components/InputError.vue';

interface PreviewRow {
    row_number: number;
    status: 'valid' | 'error';
    type: string;
    question_text: string;
    points: number | string;
    difficulty: string;
    options_count: number;
    answer_summary: string;
    error: string | null;
}

interface PreviewResult {
    total_rows: number;
    valid_count: number;
    error_count: number;
    rows: PreviewRow[];
    errors: string[];
}

const props = withDefaults(
    defineProps<{
        show: boolean;
        title?: string;
        postUrl: string;
        contextBanner?: string | null;
        categoryId?: number | string | null;
        subjectId?: number | string | null;
        showSaveToBankToggle?: boolean;
    }>(),
    {
        title: 'Import Soal dari Excel (.xlsx)',
        contextBanner: null,
        categoryId: null,
        subjectId: null,
        showSaveToBankToggle: false,
    }
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const previewing = ref(false);
const previewError = ref<string | null>(null);
const preview = ref<PreviewResult | null>(null);

const form = useForm({
    file: null as File | null,
    category_id: props.categoryId ?? null,
    subject_id: props.subjectId ?? null,
    save_to_bank: true,
});

watch(
    () => [props.show, props.categoryId, props.subjectId] as const,
    ([isOpen, catId, subjId]) => {
        if (isOpen) {
            form.clearErrors();
            form.file = null;
            form.category_id = catId ?? null;
            form.subject_id = subjId ?? null;
            form.save_to_bank = true;
            preview.value = null;
            previewError.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
    }
);

async function handleFileChange(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const selected = input.files?.[0] ?? null;
    form.file = selected;
    form.clearErrors();
    preview.value = null;
    previewError.value = null;

    if (!selected) return;

    const payload = new FormData();
    payload.append('file', selected);

    previewing.value = true;
    try {
        const res = await axios.post<PreviewResult>(route('instructor.question-bank.import-preview'), payload, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        preview.value = res.data;
    } catch (err: unknown) {
        const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
        const firstFieldErr = axiosErr.response?.data?.errors?.file?.[0];
        previewError.value = firstFieldErr ?? axiosErr.response?.data?.message ?? 'Gagal membaca pratinjau file Excel.';
    } finally {
        previewing.value = false;
    }
}

function submitImport(): void {
    if (!preview.value || preview.value.error_count > 0 || preview.value.valid_count === 0) return;
    form.post(props.postUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
}
</script>

<template>
    <AppModal :show="show" :title="title" max-width="xl" @close="emit('close')">
        <div class="space-y-4">
            <div
                v-if="contextBanner"
                class="rounded-[10px] border border-[#7692FF] bg-[#F8FAFC] px-3.5 py-2.5 text-xs font-medium text-[#1B2CC1] dark:bg-[#162030] dark:text-[#ABD2FA]"
            >
                {{ contextBanner }}
            </div>

            <!-- Step 1: Download Template -->
            <div class="rounded-[12px] border border-[#E2E8F0] bg-[#F8FAFC] p-4 dark:border-[#3E4F69] dark:bg-[#162030]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-sm font-bold text-[#1E293B] dark:text-[#F8FAFC]">
                            <FileSpreadsheet class="h-4 w-4 text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                            <span>Langkah 1: Unduh Template Excel (.xlsx)</span>
                        </div>
                        <p class="text-xs leading-relaxed text-[#475569] dark:text-[#CBD5E1]">
                            Template sudah dilengkapi contoh soal <strong>PG</strong>, <strong>Benar/Salah (BS)</strong>, dan <strong>Essay</strong> serta sheet <em>Panduan Pengisian</em>. Hapus atau timpa baris <code>[CONTOH]</code> sebelum mengunggah.
                        </p>
                    </div>
                    <a
                        :href="route('instructor.question-bank.template')"
                        class="inline-flex min-h-[40px] shrink-0 items-center gap-2 rounded-[8px] border border-[#1B2CC1] bg-white px-3.5 text-xs font-semibold text-[#1B2CC1] hover:bg-[#EFF6FF] dark:border-[#ABD2FA] dark:bg-[#283548] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69]"
                    >
                        <Download class="h-4 w-4" aria-hidden="true" />
                        Unduh Template (.xlsx)
                    </a>
                </div>
            </div>

            <!-- Step 2: Upload & Preview Excel -->
            <form @submit.prevent="submitImport" class="space-y-4">
                <div>
                    <label for="xlsx_import_file" class="mb-1.5 block text-xs font-semibold">
                        Langkah 2: Pilih File Excel (.xlsx, maksimal 5 MB / 500 soal)
                    </label>
                    <input
                        id="xlsx_import_file"
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        required
                        @change="handleFileChange"
                        class="block w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-xs file:mr-3 file:rounded-[6px] file:border-0 file:bg-[#1B2CC1] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white dark:border-[#1B2CC1] dark:bg-[#162030] dark:file:bg-[#ABD2FA] dark:file:text-[#1E293B]"
                    />
                    <InputError :message="form.errors.file || previewError || undefined" />
                </div>

                <div v-if="previewing" class="flex items-center gap-2 rounded-[8px] bg-[#F8FAFC] px-3 py-2.5 text-xs text-[#475569] dark:bg-[#162030] dark:text-[#CBD5E1]">
                    <Loader2 class="h-4 w-4 animate-spin text-[#1B2CC1] dark:text-[#ABD2FA]" aria-hidden="true" />
                    Memvalidasi &amp; menyiapkan pratinjau baris soal...
                </div>

                <!-- Preview Result -->
                <div v-if="preview" class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-[6px] bg-[#E2E8F0] px-2.5 py-1 font-semibold dark:bg-[#3E4F69]">
                            Total: {{ preview.total_rows }} baris
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-[6px] bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <CheckCircle2 class="h-3.5 w-3.5" aria-hidden="true" />
                            Valid: {{ preview.valid_count }}
                        </span>
                        <span
                            v-if="preview.error_count > 0"
                            class="inline-flex items-center gap-1 rounded-[6px] bg-red-100 px-2.5 py-1 font-semibold text-red-700 dark:bg-red-950/60 dark:text-red-300"
                        >
                            <AlertCircle class="h-3.5 w-3.5" aria-hidden="true" />
                            Error: {{ preview.error_count }}
                        </span>
                    </div>

                    <!-- Full Error List -->
                    <div
                        v-if="preview.errors.length > 0"
                        role="alert"
                        class="max-h-36 space-y-1 overflow-y-auto rounded-[10px] border border-red-300 bg-red-50 p-3 text-xs text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300"
                    >
                        <p class="font-bold">Perbaiki error berikut di file Excel sebelum mengimpor:</p>
                        <ul class="list-disc space-y-0.5 pl-4">
                            <li v-for="(err, idx) in preview.errors" :key="idx">{{ err }}</li>
                        </ul>
                    </div>

                    <!-- Preview Table -->
                    <div class="max-h-60 overflow-auto rounded-[10px] border border-[#E2E8F0] dark:border-[#3E4F69]">
                        <table class="w-full text-left text-xs">
                            <thead class="sticky top-0 border-b border-[#E2E8F0] bg-[#F8FAFC] text-[11px] uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#162030] dark:text-[#CBD5E1]">
                                <tr>
                                    <th class="px-3 py-2">Baris</th>
                                    <th class="px-3 py-2">Tipe</th>
                                    <th class="px-3 py-2">Pertanyaan</th>
                                    <th class="px-3 py-2">Poin</th>
                                    <th class="px-3 py-2">Kunci</th>
                                    <th class="px-3 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                                <tr
                                    v-for="row in preview.rows"
                                    :key="row.row_number"
                                    :class="row.status === 'error' ? 'bg-red-50/70 dark:bg-red-950/20' : ''"
                                >
                                    <td class="px-3 py-2 font-mono">#{{ row.row_number }}</td>
                                    <td class="px-3 py-2 font-semibold">{{ row.type }}</td>
                                    <td class="px-3 py-2 max-w-[260px]">
                                        <p class="truncate font-medium">{{ row.question_text }}</p>
                                        <p v-if="row.error" class="mt-0.5 text-[11px] text-red-600 dark:text-red-400">{{ row.error }}</p>
                                    </td>
                                    <td class="px-3 py-2">{{ row.points }}</td>
                                    <td class="px-3 py-2 max-w-[140px] truncate">{{ row.answer_summary }}</td>
                                    <td class="px-3 py-2">
                                        <span
                                            :class="
                                                row.status === 'valid'
                                                    ? 'text-emerald-700 font-semibold dark:text-emerald-300'
                                                    : 'text-red-600 font-semibold dark:text-red-400'
                                            "
                                        >
                                            {{ row.status === 'valid' ? 'Siap' : 'Error' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <label v-if="showSaveToBankToggle" class="inline-flex items-center gap-2 text-xs">
                    <input v-model="form.save_to_bank" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Simpan juga soal hasil impor ke Bank Soal</span>
                </label>

                <div class="flex justify-end gap-2 pt-1">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold dark:border-[#3E4F69]"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || previewing || !preview || preview.error_count > 0 || preview.valid_count === 0"
                        class="inline-flex min-h-[40px] items-center gap-2 rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white disabled:opacity-50 dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                    >
                        <Upload class="h-3.5 w-3.5" aria-hidden="true" />
                        {{ form.processing ? 'Mengimpor...' : `Import & Simpan (${preview?.valid_count ?? 0} Soal)` }}
                    </button>
                </div>
            </form>
        </div>
    </AppModal>
</template>
