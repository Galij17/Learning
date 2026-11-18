<script setup lang="ts">
import { computed, ref } from 'vue';
import { BookOpen, FileSpreadsheet, FolderOpen, Layers, Plus, Search } from 'lucide-vue-next';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

export interface SubjectWithCount {
    id: number;
    name: string;
    code: string;
    category_id: number;
    questions_count?: number;
    category?: { id: number; name: string } | null;
}

const props = defineProps<{
    subjects: SubjectWithCount[];
    categories: Array<{ id: number; name: string }>;
    unassignedCount: number;
    totalBankQuestions: number;
}>();

const emit = defineEmits<{
    openAll: [];
    openImport: [];
    openCreate: [subject: SubjectWithCount | null];
    openSubject: [id: number | 'unassigned'];
}>();

const directorySearch = ref('');
const directoryCategoryId = ref('');

const directorySubjects = computed(() => {
    const q = directorySearch.value.trim().toLowerCase();
    return props.subjects.filter((s) => {
        const matchesCat = !directoryCategoryId.value || s.category_id === Number(directoryCategoryId.value);
        const matchesQuery =
            !q ||
            s.name.toLowerCase().includes(q) ||
            s.code.toLowerCase().includes(q) ||
            (s.category?.name ?? '').toLowerCase().includes(q);
        return matchesCat && matchesQuery;
    });
});
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
            <div>
                <h2 class="text-base font-bold">Pilih Mata Pelajaran</h2>
                <p class="mt-0.5 text-xs text-[#475569] dark:text-[#CBD5E1]">
                    Soal di Bank Soal dapat dipakai ulang di berbagai kuis. Pilih mapel di bawah atau gunakan pencarian cepat ({{ totalBankQuestions }} total soal).
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="emit('openAll')"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] hover:bg-[#F8FAFC] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#1E293B]"
                >
                    <Layers class="h-4 w-4" aria-hidden="true" />
                    Lihat Semua Soal ({{ totalBankQuestions }})
                </button>
                <button
                    type="button"
                    @click="emit('openImport')"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] hover:bg-[#F8FAFC] dark:border-[#ABD2FA] dark:text-[#ABD2FA] dark:hover:bg-[#1E293B]"
                >
                    <FileSpreadsheet class="h-4 w-4" aria-hidden="true" />
                    Import Excel
                </button>
                <button
                    type="button"
                    @click="emit('openCreate', null)"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    Tambah Soal Cepat
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-[220px] flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#64748B]" aria-hidden="true" />
                <input
                    v-model="directorySearch"
                    type="search"
                    placeholder="Cari nama atau kode mata pelajaran..."
                    class="min-h-[42px] w-full rounded-[8px] border border-[#E2E8F0] bg-white pl-9 pr-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]"
                />
            </div>
            <select
                v-model="directoryCategoryId"
                aria-label="Filter kategori mata pelajaran"
                class="min-h-[42px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]"
            >
                <option value="">Semua Kategori</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>

        <EmptyState
            v-if="directorySubjects.length === 0 && unassignedCount === 0"
            :icon="BookOpen"
            title="Mata pelajaran tidak ditemukan"
            description="Sesuaikan kata kunci pencarian atau hubungi admin untuk menambahkan mata pelajaran."
        />

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <button
                v-for="subj in directorySubjects"
                :key="subj.id"
                type="button"
                @click="emit('openSubject', subj.id)"
                class="group flex flex-col justify-between rounded-[14px] border border-[#E2E8F0] bg-white p-5 text-left transition-all hover:border-[#1B2CC1] hover:shadow-sm dark:border-[#3E4F69] dark:bg-[#283548] dark:hover:border-[#ABD2FA]"
            >
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-[6px] bg-[#E2E8F0] px-2.5 py-1 font-mono text-xs font-bold text-[#1B2CC1] dark:bg-[#162030] dark:text-[#ABD2FA]">
                            <FolderOpen class="h-3.5 w-3.5" aria-hidden="true" />
                            {{ subj.code }}
                        </span>
                        <StatusBadge v-if="subj.category" :label="subj.category.name" variant="neutral" />
                    </div>
                    <h3 class="mt-3 text-base font-bold text-[#1E293B] group-hover:text-[#1B2CC1] dark:text-[#F8FAFC] dark:group-hover:text-[#ABD2FA]">
                        {{ subj.name }}
                    </h3>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-[#E2E8F0] pt-3 text-xs dark:border-[#3E4F69]">
                    <span class="font-semibold text-[#475569] dark:text-[#CBD5E1]">
                        {{ subj.questions_count ?? 0 }} Soal
                    </span>
                    <span class="font-semibold text-[#1B2CC1] dark:text-[#ABD2FA]">
                        Kelola &amp; Tambah Soal &rarr;
                    </span>
                </div>
            </button>

            <button
                v-if="unassignedCount > 0 && !directorySearch && !directoryCategoryId"
                type="button"
                @click="emit('openSubject', 'unassigned')"
                class="group flex flex-col justify-between rounded-[14px] border border-dashed border-[#CBD5E1] bg-white p-5 text-left transition-all hover:border-[#1B2CC1] dark:border-[#475569] dark:bg-[#283548] dark:hover:border-[#ABD2FA]"
            >
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-[6px] bg-[#F1F5F9] px-2.5 py-1 font-mono text-xs font-bold text-[#475569] dark:bg-[#162030] dark:text-[#CBD5E1]">
                            <FolderOpen class="h-3.5 w-3.5" aria-hidden="true" />
                            UMUM
                        </span>
                        <StatusBadge label="Tanpa Mapel" variant="amber" />
                    </div>
                    <h3 class="mt-3 text-base font-bold text-[#1E293B] dark:text-[#F8FAFC]">
                        Soal Umum (Belum Dipetakan)
                    </h3>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-[#E2E8F0] pt-3 text-xs dark:border-[#3E4F69]">
                    <span class="font-semibold text-[#475569] dark:text-[#CBD5E1]">
                        {{ unassignedCount }} Soal
                    </span>
                    <span class="font-semibold text-[#1B2CC1] dark:text-[#ABD2FA]">
                        Lihat Soal &rarr;
                    </span>
                </div>
            </button>
        </div>
    </div>
</template>
