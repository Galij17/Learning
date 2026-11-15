<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Award,
    BarChart3,
    Bell,
    BookOpen,
    CalendarRange,
    FileSpreadsheet,
    FolderTree,
    GraduationCap,
    HelpCircle,
    LayoutDashboard,
    LogOut,
    Megaphone,
    Menu,
    Moon,
    Settings,
    Sun,
    UserCheck,
    Users,
    X,
} from 'lucide-vue-next';
import AppToast from '@/Components/AppToast.vue';
import { useTheme } from '@/composables/useTheme';
import { useToast } from '@/composables/useToast';
import type { SharedPageProps } from '@/types';

interface Props {
    title?: string;
}

defineProps<Props>();

const page = usePage<SharedPageProps>();
const { isDark, toggleTheme } = useTheme();
const { notify } = useToast();

const mobileMenuOpen = ref(false);
const notifDropdownOpen = ref(false);

const user = computed(() => page.props.auth.user);
const appSettings = computed(() => page.props.appSettings);
const notifications = computed(() => page.props.notifications);

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) notify(flash.success, 'success');
        if (flash?.error) notify(flash.error, 'error');
        if (flash?.warning) notify(flash.warning, 'warning');
    },
    { immediate: true, deep: true }
);

interface NavItem {
    label: string;
    href: string;
    routePattern: string;
    icon: unknown;
}

const navItems = computed<NavItem[]>(() => {
    const role = user.value?.role;

    if (role === 'admin') {
        return [
            { label: 'Dashboard', href: route('admin.dashboard'), routePattern: 'admin.dashboard', icon: LayoutDashboard },
            { label: 'Pengguna', href: route('admin.users.index'), routePattern: 'admin.users.*', icon: Users },
            { label: 'Kategori', href: route('admin.categories.index'), routePattern: 'admin.categories.*', icon: FolderTree },
            { label: 'Mata Pelajaran', href: route('admin.subjects.index'), routePattern: 'admin.subjects.*', icon: BookOpen },
            { label: 'Periode Akademik', href: route('admin.periods.index'), routePattern: 'admin.periods.*', icon: CalendarRange },
            { label: 'Manajemen Kelas', href: route('admin.courses.index'), routePattern: 'admin.courses.*', icon: GraduationCap },
            { label: 'Pengumuman Global', href: route('admin.announcements.index'), routePattern: 'admin.announcements.*', icon: Megaphone },
            { label: 'Log Aktivitas', href: route('admin.activity-logs.index'), routePattern: 'admin.activity-logs.*', icon: Activity },
            { label: 'Laporan & Ekspor', href: route('admin.reports.index'), routePattern: 'admin.reports.*', icon: FileSpreadsheet },
            { label: 'Pengaturan', href: route('admin.settings.index'), routePattern: 'admin.settings.*', icon: Settings },
        ];
    }

    if (role === 'instructor') {
        return [
            { label: 'Dashboard Pengajar', href: route('instructor.dashboard'), routePattern: 'instructor.dashboard', icon: LayoutDashboard },
            { label: 'Kelas Saya', href: route('instructor.courses.index'), routePattern: 'instructor.courses.*', icon: GraduationCap },
            { label: 'Bank Soal', href: route('instructor.question-bank.index'), routePattern: 'instructor.question-bank.*', icon: HelpCircle },
            { label: 'Notifikasi', href: route('notifications.index'), routePattern: 'notifications.*', icon: Bell },
            { label: 'Profil', href: route('profile.edit'), routePattern: 'profile.*', icon: UserCheck },
        ];
    }

    return [
        { label: 'Dashboard Belajar', href: route('student.dashboard'), routePattern: 'student.dashboard', icon: LayoutDashboard },
        { label: 'Kelas Saya', href: route('student.courses.index'), routePattern: 'student.courses.*', icon: BookOpen },
        { label: 'Rekap Nilai', href: route('student.grades.index'), routePattern: 'student.grades.*', icon: BarChart3 },
        { label: 'Sertifikat', href: route('certificates.index'), routePattern: 'certificates.*', icon: Award },
        { label: 'Notifikasi', href: route('notifications.index'), routePattern: 'notifications.*', icon: Bell },
    ];
});

function isRouteActive(pattern: string): boolean {
    try {
        return route().current(pattern);
    } catch {
        return false;
    }
}

function logout(): void {
    router.post(route('logout'));
}

function handleEscape(e: KeyboardEvent): void {
    if (e.key === 'Escape') {
        mobileMenuOpen.value = false;
        notifDropdownOpen.value = false;
    }
}

onMounted(() => window.addEventListener('keydown', handleEscape));
onUnmounted(() => window.removeEventListener('keydown', handleEscape));
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] text-[#1E293B] dark:bg-[#1E293B] dark:text-[#F8FAFC]">
        <!-- Mobile Drawer Overlay -->
        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 z-40 bg-[#1E293B]/70 lg:hidden"
            @click="mobileMenuOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-68 flex-col border-r border-[#E2E8F0] bg-white text-[#1E293B] transition-transform duration-200 dark:border-[#3E4F69] dark:bg-[#283548] dark:text-[#F8FAFC] lg:translate-x-0',
                mobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
            aria-label="Navigasi utama"
        >
            <div class="flex h-16 items-center justify-between border-b border-[#E2E8F0] px-5 dark:border-[#3E4F69]">
                <Link :href="route('home')" class="flex items-center gap-3">
                    <img
                        v-if="appSettings.site_logo_url"
                        :src="appSettings.site_logo_url"
                        :alt="appSettings.site_name"
                        width="32"
                        height="32"
                        class="h-8 w-8 rounded-[8px] object-cover"
                    />
                    <span
                        v-else
                        class="flex h-9 w-9 items-center justify-center rounded-[8px] bg-[#1B2CC1] text-sm font-extrabold text-white"
                    >
                        {{ appSettings.site_name.slice(0, 2).toUpperCase() }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-[#1E293B] dark:text-white">{{ appSettings.site_name }}</p>
                        <p class="truncate text-[11px] text-[#475569] dark:text-[#CBD5E1]">{{ appSettings.institution_name }}</p>
                    </div>
                </Link>
                <button
                    type="button"
                    @click="mobileMenuOpen = false"
                    aria-label="Tutup menu navigasi"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-[8px] text-[#475569] hover:bg-[#F8FAFC] hover:text-[#1E293B] dark:text-[#CBD5E1] dark:hover:bg-[#3E4F69] dark:hover:text-white lg:hidden"
                >
                    <X class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    @click="mobileMenuOpen = false"
                    class="flex min-h-[44px] items-center gap-3 rounded-[10px] px-3.5 py-2.5 text-sm font-medium transition-colors"
                    :class="
                        isRouteActive(item.routePattern)
                            ? 'bg-[#1B2CC1] text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]'
                            : 'text-[#475569] hover:bg-[#F8FAFC] hover:text-[#1E293B] dark:text-[#CBD5E1] dark:hover:bg-[#3E4F69] dark:hover:text-white'
                    "
                >
                    <component :is="item.icon" class="h-4.5 w-4.5 shrink-0" aria-hidden="true" />
                    <span class="truncate">{{ item.label }}</span>
                </Link>
            </nav>

            <div class="border-t border-[#E2E8F0] p-3 dark:border-[#3E4F69]">
                <Link
                    :href="route('profile.edit')"
                    @click="mobileMenuOpen = false"
                    class="flex min-h-[44px] items-center gap-3 rounded-[10px] px-3 py-2 text-sm hover:bg-[#F8FAFC] dark:hover:bg-[#3E4F69]"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#E2E8F0] text-xs font-bold text-[#1E293B] dark:bg-[#3E4F69] dark:text-[#F8FAFC]"
                    >
                        {{ user?.name?.slice(0, 2).toUpperCase() }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-[#1E293B] dark:text-white">{{ user?.name }}</p>
                        <p class="truncate text-xs capitalize text-[#475569] dark:text-[#CBD5E1]">{{ user?.role }}</p>
                    </div>
                </Link>
                <button
                    type="button"
                    @click="logout"
                    class="mt-1.5 flex min-h-[44px] w-full items-center gap-3 rounded-[10px] px-3.5 py-2 text-sm font-medium text-[#475569] hover:bg-[#F8FAFC] hover:text-[#1E293B] dark:text-[#CBD5E1] dark:hover:bg-[#3E4F69] dark:hover:text-white"
                >
                    <LogOut class="h-4.5 w-4.5 shrink-0" aria-hidden="true" />
                    <span>Keluar</span>
                </button>
            </div>
        </aside>

        <!-- Main Column -->
        <div class="flex min-h-screen flex-col lg:pl-68">
            <header
                class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-[#E2E8F0] bg-white/95 px-4 dark:border-[#3E4F69] dark:bg-[#283548]/95 sm:px-6"
            >
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="mobileMenuOpen = true"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-[10px] border border-[#E2E8F0] px-3 text-sm font-semibold text-[#1E293B] hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:text-[#F8FAFC] dark:hover:bg-[#3E4F69] lg:hidden"
                    >
                        <Menu class="h-5 w-5" aria-hidden="true" />
                        <span>Menu</span>
                    </button>
                    <div v-if="title">
                        <h1 class="text-base font-bold text-[#1E293B] dark:text-[#F8FAFC] sm:text-lg">{{ title }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Theme Toggle -->
                    <button
                        type="button"
                        @click="toggleTheme"
                        :aria-label="isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-[10px] border border-[#E2E8F0] bg-white text-[#1E293B] hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#ABD2FA] dark:hover:bg-[#3E4F69]"
                    >
                        <Sun v-if="isDark" class="h-5 w-5" aria-hidden="true" />
                        <Moon v-else class="h-5 w-5" aria-hidden="true" />
                    </button>

                    <!-- Notifications Dropdown -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="notifDropdownOpen = !notifDropdownOpen"
                            :aria-expanded="notifDropdownOpen"
                            aria-label="Notifikasi"
                            class="relative inline-flex h-11 w-11 items-center justify-center rounded-[10px] border border-[#E2E8F0] bg-white text-[#1E293B] hover:bg-[#F8FAFC] dark:border-[#3E4F69] dark:bg-[#1E293B] dark:text-[#F8FAFC] dark:hover:bg-[#3E4F69]"
                        >
                            <Bell class="h-5 w-5" aria-hidden="true" />
                            <span
                                v-if="notifications.unread_count > 0"
                                class="absolute -top-1 -right-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#1B2CC1] px-1 text-[11px] font-bold text-white dark:bg-[#ABD2FA] dark:text-[#1E293B]"
                            >
                                {{ notifications.unread_count > 99 ? '99+' : notifications.unread_count }}
                            </span>
                        </button>

                        <div
                            v-if="notifDropdownOpen"
                            class="absolute right-0 mt-2 w-80 rounded-[14px] border border-[#E2E8F0] bg-white p-3 shadow-xl dark:border-[#3E4F69] dark:bg-[#283548] sm:w-96"
                        >
                            <div class="mb-2 flex items-center justify-between px-1">
                                <span class="text-sm font-bold">Notifikasi Terbaru</span>
                                <Link
                                    :href="route('notifications.index')"
                                    @click="notifDropdownOpen = false"
                                    class="text-xs font-semibold text-[#1B2CC1] hover:underline dark:text-[#ABD2FA]"
                                >
                                    Lihat semua
                                </Link>
                            </div>
                            <div v-if="notifications.recent.length === 0" class="py-6 text-center text-sm text-[#475569] dark:text-[#CBD5E1]">
                                Belum ada notifikasi.
                            </div>
                            <ul v-else class="max-h-72 space-y-1.5 overflow-y-auto">
                                <li
                                    v-for="item in notifications.recent"
                                    :key="item.id"
                                    class="rounded-[10px] p-2.5 text-sm"
                                    :class="item.read_at ? 'bg-transparent' : 'bg-[#F8FAFC] dark:bg-[#3E4F69]/60'"
                                >
                                    <p class="font-semibold text-[#1E293B] dark:text-[#F8FAFC]">{{ item.data.title }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ item.data.message }}</p>
                                    <p class="mt-1 text-[11px] text-[#475569] dark:text-[#CBD5E1]">{{ item.created_at_human }}</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>

        <AppToast />
    </div>
</template>
