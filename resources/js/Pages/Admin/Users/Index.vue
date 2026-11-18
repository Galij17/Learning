<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { KeyRound, Plus, Power, Search, Trash2, Users } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppModal from '@/Components/AppModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { PaginatedData } from '@/types';

interface UserRow {
    id: number;
    name: string;
    email: string;
    identity_number: string | null;
    phone: string | null;
    bio: string | null;
    is_active: boolean;
    role: 'admin' | 'instructor' | 'student';
    role_label: string;
    last_login_at: string | null;
    created_at: string;
}

const props = defineProps<{
    users: PaginatedData<UserRow>;
    roles: Array<{ id: number; name: string; display_name: string }>;
    filters: { search?: string; role?: string; status?: string };
}>();

const search = ref(props.filters.search ?? '');
const roleFilter = ref(props.filters.role ?? '');
const statusFilter = ref(props.filters.status ?? '');

const showFormModal = ref(false);
const editingUser = ref<UserRow | null>(null);
const resetTarget = ref<UserRow | null>(null);

const userForm = useForm({
    name: '',
    email: '',
    identity_number: '',
    phone: '',
    bio: '',
    role: 'student',
    is_active: true,
    password: '',
});

const resetForm = useForm({
    password: '',
    password_confirmation: '',
});

function applyFilters(): void {
    router.get(
        route('admin.users.index'),
        { search: search.value, role: roleFilter.value, status: statusFilter.value },
        { preserveState: true, replace: true }
    );
}

function openCreate(): void {
    editingUser.value = null;
    userForm.reset();
    userForm.clearErrors();
    userForm.is_active = true;
    userForm.role = 'student';
    showFormModal.value = true;
}

function openEdit(user: UserRow): void {
    editingUser.value = user;
    userForm.clearErrors();
    userForm.name = user.name;
    userForm.email = user.email;
    userForm.identity_number = user.identity_number ?? '';
    userForm.phone = user.phone ?? '';
    userForm.bio = user.bio ?? '';
    userForm.role = user.role;
    userForm.is_active = user.is_active;
    userForm.password = '';
    showFormModal.value = true;
}

function submitUser(): void {
    if (editingUser.value) {
        userForm.put(route('admin.users.update', editingUser.value.id), {
            onSuccess: () => {
                showFormModal.value = false;
                userForm.reset();
            },
        });
    } else {
        userForm.post(route('admin.users.store'), {
            onSuccess: () => {
                showFormModal.value = false;
                userForm.reset();
            },
        });
    }
}

function toggleStatus(user: UserRow): void {
    router.patch(route('admin.users.toggle-status', user.id), {}, { preserveScroll: true });
}

const deletingUser = ref<UserRow | null>(null);
const isDeletingUser = ref(false);

function openResetPassword(user: UserRow): void {
    resetTarget.value = user;
    resetForm.reset();
    resetForm.clearErrors();
}

function submitResetPassword(): void {
    if (!resetTarget.value) return;
    resetForm.put(route('admin.users.reset-password', resetTarget.value.id), {
        onSuccess: () => {
            resetTarget.value = null;
            resetForm.reset();
        },
    });
}

function deleteUser(user: UserRow): void {
    deletingUser.value = user;
}

function confirmDeleteUser(): void {
    if (!deletingUser.value) return;
    isDeletingUser.value = true;
    router.delete(route('admin.users.destroy', deletingUser.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeletingUser.value = false;
            deletingUser.value = null;
        },
    });
}
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <AuthenticatedLayout title="Manajemen Pengguna">
        <div class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="applyFilters" class="flex flex-1 flex-wrap gap-2">
                    <div class="relative min-w-[220px] flex-1 sm:max-w-xs">
                        <Search class="pointer-events-none absolute top-3 left-3 h-4 w-4 text-[#475569]" aria-hidden="true" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Cari nama, email, NIM/NIP..."
                            class="min-h-[44px] w-full rounded-[8px] border border-[#E2E8F0] bg-white pr-3 pl-9 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]"
                        />
                    </div>
                    <select
                        v-model="roleFilter"
                        @change="applyFilters"
                        aria-label="Filter role"
                        class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]"
                    >
                        <option value="">Semua Role</option>
                        <option v-for="r in roles" :key="r.name" :value="r.name">{{ r.display_name }}</option>
                    </select>
                    <select
                        v-model="statusFilter"
                        @change="applyFilters"
                        aria-label="Filter status akun"
                        class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] bg-white px-3 text-sm dark:border-[#1B2CC1] dark:bg-[#283548]"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <button
                        type="submit"
                        class="min-h-[44px] rounded-[8px] border border-[#1B2CC1] px-4 text-sm font-semibold text-[#1B2CC1] dark:border-[#ABD2FA] dark:text-[#ABD2FA]"
                    >
                        Terapkan
                    </button>
                </form>

                <button
                    type="button"
                    @click="openCreate"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-[10px] bg-[#1B2CC1] px-4 text-sm font-semibold text-white hover:bg-[#142194] dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                >
                    <Plus class="h-4 w-4" aria-hidden="true" />
                    <span>Tambah Pengguna</span>
                </button>
            </div>

            <EmptyState
                v-if="users.data.length === 0"
                :icon="Users"
                title="Tidak ada pengguna sesuai filter"
                description="Ubah kata kunci pencarian atau tambah akun pengguna baru."
            />

            <div v-else class="overflow-hidden rounded-[14px] border border-[#E2E8F0] bg-white dark:border-[#3E4F69] dark:bg-[#283548]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs uppercase text-[#475569] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#CBD5E1]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nama & Email</th>
                                <th class="px-4 py-3 font-semibold">NIM / NIP</th>
                                <th class="px-4 py-3 font-semibold">Role</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Login Terakhir</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0] dark:divide-[#3E4F69]">
                            <tr v-for="u in users.data" :key="u.id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ u.name }}</p>
                                    <p class="text-xs text-[#475569] dark:text-[#CBD5E1]">{{ u.email }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">{{ u.identity_number || '-' }}</td>
                                <td class="px-4 py-3">
                                    <StatusBadge :label="u.role_label" :variant="u.role === 'admin' ? 'pine' : u.role === 'instructor' ? 'mint' : 'neutral'" />
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :label="u.is_active ? 'Aktif' : 'Nonaktif'" :variant="u.is_active ? 'mint' : 'danger'" />
                                </td>
                                <td class="px-4 py-3 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ u.last_login_at || 'Belum pernah' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button" @click="openEdit(u)" class="min-h-[38px] rounded-[8px] border border-[#E2E8F0] px-2.5 text-xs font-semibold hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]">
                                            Edit
                                        </button>
                                        <button type="button" @click="toggleStatus(u)" :title="u.is_active ? 'Nonaktifkan' : 'Aktifkan'" :aria-label="u.is_active ? 'Nonaktifkan akun' : 'Aktifkan akun'" class="inline-flex min-h-[38px] min-w-[38px] items-center justify-center rounded-[8px] border border-[#E2E8F0] hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]">
                                            <Power class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                        <button type="button" @click="openResetPassword(u)" title="Reset Password" aria-label="Reset password" class="inline-flex min-h-[38px] min-w-[38px] items-center justify-center rounded-[8px] border border-[#E2E8F0] hover:bg-[#F8FAFC] dark:border-[#1B2CC1] dark:hover:bg-[#3E4F69]">
                                            <KeyRound class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                        <button type="button" @click="deleteUser(u)" title="Hapus" aria-label="Hapus pengguna" class="inline-flex min-h-[38px] min-w-[38px] items-center justify-center rounded-[8px] border border-red-200 text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400">
                                            <Trash2 class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationBar :links="users.links" :from="users.from" :to="users.to" :total="users.total" />
            </div>
        </div>

        <!-- Create/Edit User Modal -->
        <AppModal :show="showFormModal" :title="editingUser ? 'Edit Pengguna' : 'Tambah Pengguna Baru'" @close="showFormModal = false">
            <form @submit.prevent="submitUser" class="space-y-4">
                <div>
                    <label for="u-name" class="block text-sm font-semibold">Nama Lengkap</label>
                    <input id="u-name" v-model="userForm.name" type="text" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <p v-if="userForm.errors.name" class="mt-1 text-xs text-red-600">{{ userForm.errors.name }}</p>
                </div>
                <div>
                    <label for="u-email" class="block text-sm font-semibold">Email</label>
                    <input id="u-email" v-model="userForm.email" type="email" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <p v-if="userForm.errors.email" class="mt-1 text-xs text-red-600">{{ userForm.errors.email }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="u-id" class="block text-sm font-semibold">NIM / NIP</label>
                        <input id="u-id" v-model="userForm.identity_number" type="text" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                        <p v-if="userForm.errors.identity_number" class="mt-1 text-xs text-red-600">{{ userForm.errors.identity_number }}</p>
                    </div>
                    <div>
                        <label for="u-role" class="block text-sm font-semibold">Role</label>
                        <select id="u-role" v-model="userForm.role" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]">
                            <option v-for="r in roles" :key="r.name" :value="r.name">{{ r.display_name }}</option>
                        </select>
                        <InputError :message="userForm.errors.role" />
                    </div>
                </div>
                <div v-if="!editingUser">
                    <label for="u-pw" class="block text-sm font-semibold">Kata Sandi Awal</label>
                    <input id="u-pw" v-model="userForm.password" type="password" required placeholder="Minimal 8 karakter (huruf & angka)" class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <p v-if="userForm.errors.password" class="mt-1 text-xs text-red-600">{{ userForm.errors.password }}</p>
                </div>
                <label class="inline-flex min-h-[44px] items-center gap-2 text-sm">
                    <input v-model="userForm.is_active" type="checkbox" class="h-4 w-4 rounded" />
                    <span>Akun aktif (dapat login)</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showFormModal = false" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="userForm.processing" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">
                        {{ editingUser ? 'Simpan Perubahan' : 'Buat Pengguna' }}
                    </button>
                </div>
            </form>
        </AppModal>

        <!-- Reset Password Modal -->
        <AppModal :show="Boolean(resetTarget)" :title="`Reset Kata Sandi: ${resetTarget?.name ?? ''}`" @close="resetTarget = null">
            <form @submit.prevent="submitResetPassword" class="space-y-4">
                <div>
                    <label for="rp-pw" class="block text-sm font-semibold">Kata Sandi Baru</label>
                    <input id="rp-pw" v-model="resetForm.password" type="password" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <p v-if="resetForm.errors.password" class="mt-1 text-xs text-red-600">{{ resetForm.errors.password }}</p>
                </div>
                <div>
                    <label for="rp-pwc" class="block text-sm font-semibold">Konfirmasi Kata Sandi Baru</label>
                    <input id="rp-pwc" v-model="resetForm.password_confirmation" type="password" required class="mt-1 w-full rounded-[8px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm dark:border-[#1B2CC1] dark:bg-[#1E293B]" />
                    <InputError :message="resetForm.errors.password_confirmation" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="resetTarget = null" class="min-h-[44px] rounded-[8px] border border-[#E2E8F0] px-4 text-sm font-semibold dark:border-[#1B2CC1]">Batal</button>
                    <button type="submit" :disabled="resetForm.processing" class="min-h-[44px] rounded-[8px] bg-[#1B2CC1] px-5 text-sm font-semibold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]">Reset Kata Sandi</button>
                </div>
            </form>
        </AppModal>

        <ConfirmModal
            :show="Boolean(deletingUser)"
            title="Hapus Akun Pengguna"
            :message="`Hapus akun ${deletingUser?.name ?? ''} (${deletingUser?.email ?? ''})?`"
            confirm-text="Hapus Akun"
            :processing="isDeletingUser"
            @confirm="confirmDeleteUser"
            @cancel="deletingUser = null"
        />
    </AuthenticatedLayout>
</template>
