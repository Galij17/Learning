<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { BookOpen, KeyRound } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Enrollment, PaginatedData } from '@/types';

const props = defineProps<{
    enrollments: PaginatedData<Enrollment>;
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');
const showJoinModal = ref(false);

const joinForm = useForm({
    code: '',
});

function applySearch(): void {
    router.get(route('student.courses.index'), { search: search.value || undefined }, { preserveState: true, replace: true });
}

function submitJoin(): void {
    joinForm.post(route('student.courses.join'), {
        onSuccess: () => {
            showJoinModal.value = false;
            joinForm.reset();
        },
    });
}
</script>

<template>
    <Head title="Kelas Saya" />

    <AuthenticatedLayout title="Daftar Kelas Saya">
        <div class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form @submit.prevent="applySearch" class="flex gap-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari judul atau kode kelas..."
                        class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3.5 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]"
                    />
                    <button type="submit" class="min-h-[44px] rounded-[8px] border border-[#1B2CC1] px-4 text-xs font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]">
                        Cari
                    </button>
                </form>

                <button
                    type="button"
                    @click="showJoinModal = true"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    <KeyRound class="h-4 w-4" aria-hidden="true" />
                    Gabung Pakai Kode
                </button>
            </div>

            <EmptyState
                v-if="enrollments.data.length === 0"
                :icon="BookOpen"
                title="Belum ada kelas diikuti"
                description="Masukkan kode kelas dari pengajar untuk mulai mengakses materi dan tugas."
            />

            <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="enr in enrollments.data"
                    :key="enr.id"
                    class="flex flex-col justify-between rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]"
                >
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="rounded-[6px] bg-[#E2E8F0] px-2.5 py-1 font-mono text-xs font-bold text-[#1E293B] dark:bg-[#3E4F69] dark:text-[#ABD2FA]">
                                {{ enr.course?.code }}
                            </span>
                            <StatusBadge :label="enr.status" :variant="enr.status === 'completed' ? 'mint' : 'neutral'" />
                        </div>
                        <h3 class="mt-3 text-base font-bold">{{ enr.course?.title }}</h3>
                        <p class="mt-1 text-xs text-[#475569] dark:text-[#CBD5E1]">
                            {{ enr.course?.instructor?.name }} • {{ enr.course?.category?.name }}
                        </p>
                        <p class="mt-2 line-clamp-2 text-sm text-[#475569] dark:text-[#CBD5E1]">
                            {{ enr.course?.description || 'Klik masuk kelas untuk mempelajari materi.' }}
                        </p>
                    </div>

                    <div class="mt-5 border-t border-[#E2E8F0] pt-4 dark:border-[#3E4F69]">
                        <div class="mb-3">
                            <div class="mb-1 flex justify-between text-xs">
                                <span>Progress Materi</span>
                                <span class="font-bold">{{ Number(enr.progress_percentage).toFixed(0) }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-[#E2E8F0] dark:bg-[#3E4F69]">
                                <div class="h-full bg-[#7692FF]" :style="{ width: `${Math.min(100, Number(enr.progress_percentage))}%` }" />
                            </div>
                        </div>
                        <Link
                            :href="route('student.courses.show', enr.course_id)"
                            class="inline-flex min-h-[42px] w-full items-center justify-center rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                        >
                            Masuk Kelas
                        </Link>
                    </div>
                </article>
            </div>

            <PaginationBar :links="enrollments.links" :from="enrollments.from" :to="enrollments.to" :total="enrollments.total" />
        </div>

        <AppModal :show="showJoinModal" title="Gabung Kelas Baru" @close="showJoinModal = false">
            <form @submit.prevent="submitJoin" class="space-y-4">
                <div>
                    <label for="modal_code" class="mb-1 block text-xs font-semibold">Kode Kelas</label>
                    <input
                        id="modal_code"
                        v-model="joinForm.code"
                        type="text"
                        required
                        placeholder="Masukkan kode kelas..."
                        class="w-full min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-3 font-mono text-sm uppercase dark:border-[#1B2CC1] dark:bg-[#162030]"
                    />
                    <p v-if="joinForm.errors.code" class="mt-1 text-xs text-[#DC2626]">{{ joinForm.errors.code }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showJoinModal = false" class="min-h-[40px] rounded-[8px] border border-[#E2E8F0] px-4 text-xs font-semibold">Batal</button>
                    <button type="submit" :disabled="joinForm.processing" class="min-h-[40px] rounded-[8px] bg-[#1B2CC1] px-4 text-xs font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        Gabung Sekarang
                    </button>
                </div>
            </form>
        </AppModal>
    </AuthenticatedLayout>
</template>
