<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { UserPlus } from 'lucide-vue-next';
import AppModal from '@/Components/AppModal.vue';
import InputError from '@/Components/InputError.vue';
import type { Enrollment } from '@/types';

const props = defineProps<{
    courseId: number;
    enrollments: Enrollment[];
    availableStudents: Array<{ id: number; name: string; email: string; identity_number: string | null }>;
}>();

const emit = defineEmits<{
    removeStudent: [studentId: number];
}>();

const showEnrollModal = ref(false);

const enrollForm = useForm({
    student_id: '' as number | string,
});

function submitEnroll(): void {
    enrollForm.post(route('instructor.courses.students.enroll', props.courseId), {
        onSuccess: () => {
            showEnrollModal.value = false;
            enrollForm.reset();
        },
    });
}
</script>

<template>
    <section class="space-y-4">
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
                    <tr v-if="enrollments.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-[#475569] dark:text-[#CBD5E1]">Belum ada peserta di kelas ini.</td>
                    </tr>
                    <tr v-for="enr in enrollments" :key="enr.id">
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ enr.student?.name }}</p>
                            <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ enr.student?.email }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ enr.student?.identity_number || '-' }}</td>
                        <td class="px-4 py-3">{{ Number(enr.progress_percentage).toFixed(0) }}%</td>
                        <td class="px-4 py-3 font-bold">{{ enr.final_score ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" @click="emit('removeStudent', enr.student_id)" class="text-xs font-semibold text-[#DC2626] hover:underline">
                                Keluarkan
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

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
    </section>
</template>
