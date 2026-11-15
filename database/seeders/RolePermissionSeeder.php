<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Pengelola penuh platform, pengguna, master data akademik, laporan, dan konfigurasi.',
            ],
            [
                'name' => 'instructor',
                'display_name' => 'Pengajar / Dosen',
                'description' => 'Mengelola kelas, materi pembelajaran, tugas, kuis, ujian, penilaian, dan diskusi.',
            ],
            [
                'name' => 'student',
                'display_name' => 'Siswa / Mahasiswa',
                'description' => 'Mengikuti kelas, mempelajari materi, mengumpulkan tugas, mengerjakan ujian, dan meraih sertifikat.',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::query()->updateOrCreate(['name' => $roleData['name']], $roleData);
        }

        $permissions = [
            ['name' => 'users.manage', 'display_name' => 'Kelola Pengguna', 'group' => 'admin'],
            ['name' => 'academic.manage', 'display_name' => 'Kelola Kategori, Mapel & Periode', 'group' => 'admin'],
            ['name' => 'reports.export', 'display_name' => 'Ekspor Laporan PDF & Excel', 'group' => 'admin'],
            ['name' => 'settings.manage', 'display_name' => 'Kelola Pengaturan Aplikasi', 'group' => 'admin'],
            ['name' => 'logs.view', 'display_name' => 'Pantau Aktivitas Sistem', 'group' => 'admin'],
            ['name' => 'announcements.global', 'display_name' => 'Kelola Pengumuman Global', 'group' => 'admin'],
            ['name' => 'courses.manage', 'display_name' => 'Kelola Kelas & Peserta', 'group' => 'instructor'],
            ['name' => 'materials.manage', 'display_name' => 'Unggah & Atur Materi', 'group' => 'instructor'],
            ['name' => 'assignments.manage', 'display_name' => 'Buat Tugas & Nilai Pengumpulan', 'group' => 'instructor'],
            ['name' => 'quizzes.manage', 'display_name' => 'Kelola Bank Soal, Kuis & Ujian', 'group' => 'instructor'],
            ['name' => 'grades.manage', 'display_name' => 'Atur Bobot & Rekap Nilai', 'group' => 'instructor'],
            ['name' => 'courses.enroll', 'display_name' => 'Gabung Kelas dengan Kode', 'group' => 'student'],
            ['name' => 'assignments.submit', 'display_name' => 'Kumpulkan Tugas', 'group' => 'student'],
            ['name' => 'quizzes.attempt', 'display_name' => 'Kerjakan Kuis & Ujian', 'group' => 'student'],
            ['name' => 'certificates.view', 'display_name' => 'Lihat & Unduh Sertifikat', 'group' => 'student'],
            ['name' => 'discussions.participate', 'display_name' => 'Berpartisipasi di Forum Diskusi', 'group' => 'shared'],
        ];

        foreach ($permissions as $permData) {
            Permission::query()->updateOrCreate(['name' => $permData['name']], $permData);
        }

        $adminRole = Role::where('name', 'admin')->first();
        $instructorRole = Role::where('name', 'instructor')->first();
        $studentRole = Role::where('name', 'student')->first();

        $adminRole->permissions()->sync(Permission::pluck('id'));

        $instructorRole->permissions()->sync(
            Permission::whereIn('name', [
                'courses.manage',
                'materials.manage',
                'assignments.manage',
                'quizzes.manage',
                'grades.manage',
                'discussions.participate',
            ])->pluck('id')
        );

        $studentRole->permissions()->sync(
            Permission::whereIn('name', [
                'courses.enroll',
                'assignments.submit',
                'quizzes.attempt',
                'certificates.view',
                'discussions.participate',
            ])->pluck('id')
        );
    }
}
