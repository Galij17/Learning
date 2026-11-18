<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $instructorRole = Role::where('name', 'instructor')->firstOrFail();
        $studentRole = Role::where('name', 'student')->firstOrFail();

        $password = Hash::make('Password123!');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@verdantlms.test'],
            [
                'name' => 'Raka Pradipta',
                'identity_number' => 'ADM-001',
                'phone' => '081200001001',
                'bio' => 'Administrator Sistem Akademik VerdantLMS.',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => $password,
            ]
        );
        $admin->roles()->sync([$adminRole->id]);

        $instructor1 = User::query()->updateOrCreate(
            ['email' => 'dosen@verdantlms.test'],
            [
                'name' => 'Dr. Nadia Kusuma',
                'identity_number' => 'DSN-202601',
                'phone' => '081200002001',
                'bio' => 'Dosen Rekayasa Perangkat Lunak dan Arsitektur Web.',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => $password,
            ]
        );
        $instructor1->roles()->sync([$instructorRole->id]);

        $instructor2 = User::query()->updateOrCreate(
            ['email' => 'pengajar2@verdantlms.test'],
            [
                'name' => 'Bagus Santoso, M.Kom.',
                'identity_number' => 'DSN-202602',
                'phone' => '081200002002',
                'bio' => 'Pengajar Basis Data Relasional dan Keamanan Sistem.',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => $password,
            ]
        );
        $instructor2->roles()->sync([$instructorRole->id]);

        $studentsData = [
            ['email' => 'siswa@verdantlms.test', 'name' => 'Siti Aisyah Putri', 'identity_number' => 'MHS-26001'],
            ['email' => 'mahasiswa2@verdantlms.test', 'name' => 'Dimas Pratama', 'identity_number' => 'MHS-26002'],
            ['email' => 'mahasiswa3@verdantlms.test', 'name' => 'Clarissa Wijaya', 'identity_number' => 'MHS-26003'],
            ['email' => 'mahasiswa4@verdantlms.test', 'name' => 'Fajar Nugroho', 'identity_number' => 'MHS-26004'],
            ['email' => 'mahasiswa5@verdantlms.test', 'name' => 'Intan Permatasari', 'identity_number' => 'MHS-26005'],
        ];

        $students = [];
        foreach ($studentsData as $item) {
            $student = User::query()->updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['name'],
                    'identity_number' => $item['identity_number'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => $password,
                ]
            );
            $student->roles()->sync([$studentRole->id]);
            $students[] = $student;
        }

        // Settings
        $defaultSettings = [
            ['key' => 'site_name', 'value' => 'VerdantLMS', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'site_tagline', 'value' => 'Platform Manajemen Pembelajaran Terpadu', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'institution_name', 'value' => 'Universitas Tawangmangu', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'support_email', 'value' => 'akademik@verdantlms.test', 'group' => 'general', 'type' => 'string'],
            ['key' => 'allow_registration', 'value' => '1', 'group' => 'auth', 'type' => 'boolean'],
            ['key' => 'default_passing_score', 'value' => '70', 'group' => 'academic', 'type' => 'integer'],
            ['key' => 'site_logo_path', 'value' => '', 'group' => 'branding', 'type' => 'string'],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }

        // Academic Periods
        $periodActive = AcademicPeriod::query()->updateOrCreate(
            ['code' => '2026-1'],
            [
                'name' => 'Tahun Akademik 2026/2027 Ganjil',
                'start_date' => '2026-09-01',
                'end_date' => '2027-01-31',
                'is_active' => true,
            ]
        );

        AcademicPeriod::query()->updateOrCreate(
            ['code' => '2025-2'],
            [
                'name' => 'Tahun Akademik 2025/2026 Genap',
                'start_date' => '2026-02-01',
                'end_date' => '2026-06-30',
                'is_active' => false,
            ]
        );

        // Categories & Subjects
        $catWeb = Category::query()->updateOrCreate(
            ['slug' => 'rekayasa-perangkat-lunak'],
            ['name' => 'Rekayasa Perangkat Lunak', 'description' => 'Mata kuliah pengembangan aplikasi web, mobile, dan arsitektur sistem.', 'is_active' => true]
        );

        $catData = Category::query()->updateOrCreate(
            ['slug' => 'sistem-basis-data'],
            ['name' => 'Sistem Basis Data', 'description' => 'Desain relasional, normalisasi, query optimization, dan keamanan data.', 'is_active' => true]
        );

        $catDesign = Category::query()->updateOrCreate(
            ['slug' => 'desain-interaksi'],
            ['name' => 'Desain Interaksi', 'description' => 'Riset pengguna, aksesibilitas antarmuka, dan sistem desain.', 'is_active' => true]
        );

        $subjWeb = Subject::query()->updateOrCreate(
            ['code' => 'IF-301'],
            ['category_id' => $catWeb->id, 'name' => 'Pemrograman Web Lanjut', 'description' => 'Pengembangan aplikasi web modern dengan Laravel dan Vue.', 'is_active' => true]
        );

        $subjDb = Subject::query()->updateOrCreate(
            ['code' => 'IF-204'],
            ['category_id' => $catData->id, 'name' => 'Manajemen Basis Data', 'description' => 'Perancangan ERD, SQL transaksi, dan indexing.', 'is_active' => true]
        );

        $subjUx = Subject::query()->updateOrCreate(
            ['code' => 'DK-210'],
            ['category_id' => $catDesign->id, 'name' => 'Desain Antarmuka Pengguna', 'description' => 'Prinsip tipografi, kontras WCAG, dan tata letak responsif.', 'is_active' => true]
        );

        // Courses
        $course1 = Course::query()->updateOrCreate(
            ['code' => 'WEB2026'],
            [
                'category_id' => $catWeb->id,
                'subject_id' => $subjWeb->id,
                'academic_period_id' => $periodActive->id,
                'instructor_id' => $instructor1->id,
                'title' => 'Arsitektur Aplikasi Web Modern dengan Laravel & Vue 3',
                'slug' => 'arsitektur-aplikasi-web-modern-laravel-vue-3',
                'description' => 'Kelas komprehensif membangun aplikasi web terstruktur menggunakan Laravel MVC, Inertia.js, Vue 3 Composition API, dan TypeScript.',
                'status' => 'published',
                'assignment_weight' => 30,
                'quiz_weight' => 30,
                'exam_weight' => 40,
                'min_completion_percentage' => 50,
                'min_passing_score' => 70,
                'certificate_enabled' => true,
            ]
        );

        $course2 = Course::query()->updateOrCreate(
            ['code' => 'DB2026'],
            [
                'category_id' => $catData->id,
                'subject_id' => $subjDb->id,
                'academic_period_id' => $periodActive->id,
                'instructor_id' => $instructor2->id,
                'title' => 'Perancangan Basis Data Relasional & Optimasi Query',
                'slug' => 'perancangan-basis-data-relasional-optimasi-query',
                'description' => 'Mempelajari pemodelan ERD, normalisasi hingga BCNF, strategi indexing MySQL, dan integritas transaksi ACID.',
                'status' => 'published',
                'assignment_weight' => 35,
                'quiz_weight' => 25,
                'exam_weight' => 40,
                'min_completion_percentage' => 75,
                'min_passing_score' => 70,
                'certificate_enabled' => true,
            ]
        );

        $course3 = Course::query()->updateOrCreate(
            ['code' => 'UIX2026'],
            [
                'category_id' => $catDesign->id,
                'subject_id' => $subjUx->id,
                'academic_period_id' => $periodActive->id,
                'instructor_id' => $instructor1->id,
                'title' => 'Sistem Desain & Aksesibilitas Antarmuka Web',
                'slug' => 'sistem-desain-aksesibilitas-antarmuka-web',
                'description' => 'Merancang komponen UI yang konsisten, ramah keyboard, dan memenuhi standar kontras WCAG AA.',
                'status' => 'published',
                'assignment_weight' => 40,
                'quiz_weight' => 30,
                'exam_weight' => 30,
                'min_completion_percentage' => 80,
                'min_passing_score' => 75,
                'certificate_enabled' => true,
            ]
        );

        // Enrollments
        foreach ($students as $idx => $student) {
            Enrollment::query()->updateOrCreate(
                ['course_id' => $course1->id, 'student_id' => $student->id],
                ['status' => $idx === 0 ? 'completed' : 'active', 'progress_percentage' => $idx === 0 ? 100 : 33.33, 'final_score' => $idx === 0 ? 88.50 : null, 'enrolled_at' => now()->subDays(14), 'completed_at' => $idx === 0 ? now()->subDay() : null]
            );

            if ($idx < 3) {
                Enrollment::query()->updateOrCreate(
                    ['course_id' => $course2->id, 'student_id' => $student->id],
                    ['status' => 'active', 'progress_percentage' => 50.00, 'enrolled_at' => now()->subDays(10)]
                );
            }

            if ($idx >= 2) {
                Enrollment::query()->updateOrCreate(
                    ['course_id' => $course3->id, 'student_id' => $student->id],
                    ['status' => 'active', 'progress_percentage' => 25.00, 'enrolled_at' => now()->subDays(6)]
                );
            }
        }

        // Lessons & Materials for Course 1
        $lesson1 = Lesson::query()->updateOrCreate(
            ['course_id' => $course1->id, 'meeting_number' => 1],
            ['title' => 'Pertemuan 1: Pola MVC & Service Layer di Laravel', 'description' => 'Memahami pemisahan tanggung jawab antara Controller, Form Request, Policy, dan Service.', 'sort_order' => 1, 'is_published' => true]
        );

        $lesson2 = Lesson::query()->updateOrCreate(
            ['course_id' => $course1->id, 'meeting_number' => 2],
            ['title' => 'Pertemuan 2: Reaktivitas Vue 3 Composition API & Inertia', 'description' => 'Membangun halaman SPA tanpa API boilerplate menggunakan Inertia.js dan TypeScript.', 'sort_order' => 2, 'is_published' => true]
        );

        $lesson3 = Lesson::query()->updateOrCreate(
            ['course_id' => $course1->id, 'meeting_number' => 3],
            ['title' => 'Pertemuan 3: Keamanan Web, Validasi & Otentikasi', 'description' => 'Proteksi CSRF, otorisasi berbasis Policy, serta penyimpanan file privat.', 'sort_order' => 3, 'is_published' => true]
        );

        $mat1 = Material::query()->updateOrCreate(
            ['lesson_id' => $lesson1->id, 'title' => 'Ringkasan Arsitektur Service Layer'],
            [
                'course_id' => $course1->id,
                'type' => 'text',
                'content' => "Service layer memisahkan logika bisnis inti dari HTTP controller.\n\nManfaat utama:\n1. Controller tetap ringkas dan hanya mengurus request/response.\n2. Logika dapat dipakai ulang di CLI command, job queue, dan test.\n3. Transaksi database lebih mudah dikendalikan secara terpusat.",
                'sort_order' => 1,
                'is_published' => true,
            ]
        );

        $mat2 = Material::query()->updateOrCreate(
            ['lesson_id' => $lesson2->id, 'title' => 'Dokumentasi Referensi Inertia.js & Vue 3'],
            [
                'course_id' => $course1->id,
                'type' => 'link',
                'video_url' => 'https://inertiajs.com',
                'content' => 'Pelajari dokumentasi resmi shared props, form helper, dan partial reloads pada Inertia.js.',
                'sort_order' => 1,
                'is_published' => true,
            ]
        );

        $mat3 = Material::query()->updateOrCreate(
            ['lesson_id' => $lesson3->id, 'title' => 'Daftar Periksa Keamanan Aplikasi Laravel'],
            [
                'course_id' => $course1->id,
                'type' => 'text',
                'content' => "Pastikan selalu:\n- Validasi input menggunakan FormRequest.\n- Gunakan Policy untuk setiap resource.\n- Gunakan Eloquent atau parameter binding.\n- Simpan dokumen sensitif pada disk privat (storage/app/private).",
                'sort_order' => 1,
                'is_published' => true,
            ]
        );

        // Lessons for Course 2
        $c2l1 = Lesson::query()->updateOrCreate(
            ['course_id' => $course2->id, 'meeting_number' => 1],
            ['title' => 'Pertemuan 1: Pemodelan ERD & Normalisasi', 'description' => 'Menghindari anomali insert, update, dan delete dengan normalisasi terstruktur.', 'sort_order' => 1, 'is_published' => true]
        );
        $c2l2 = Lesson::query()->updateOrCreate(
            ['course_id' => $course2->id, 'meeting_number' => 2],
            ['title' => 'Pertemuan 2: Strategi Indexing & Optimasi Query SQL', 'description' => 'Memahami B-Tree index, composite index, dan analisis EXPLAIN.', 'sort_order' => 2, 'is_published' => true]
        );
        $c2m1 = Material::query()->updateOrCreate(
            ['lesson_id' => $c2l1->id, 'title' => 'Panduan Normalisasi 1NF hingga BCNF'],
            ['course_id' => $course2->id, 'type' => 'text', 'content' => "Setiap atribut non-kunci harus bergantung penuh hanya pada kunci kandidat.\n\nTahapan:\n- 1NF: Nilai kolom bersifat atomik.\n- 2NF: Tidak ada ketergantungan parsial.\n- 3NF: Tidak ada ketergantungan transitif.", 'sort_order' => 1, 'is_published' => true]
        );
        Material::query()->updateOrCreate(
            ['lesson_id' => $c2l2->id, 'title' => 'Cheat Sheet Indexing & EXPLAIN MySQL'],
            ['course_id' => $course2->id, 'type' => 'text', 'content' => "Gunakan EXPLAIN sebelum mengoptimasi query lambat.\nPerhatikan kolom type (ALL vs ref/range), key, dan rows.", 'sort_order' => 1, 'is_published' => true]
        );

        // Lessons for Course 3
        $c3l1 = Lesson::query()->updateOrCreate(
            ['course_id' => $course3->id, 'meeting_number' => 1],
            ['title' => 'Pertemuan 1: Prinsip Aksesibilitas WCAG AA & Tipografi', 'description' => 'Memastikan rasio kontras warna >= 4.5:1 dan navigasi keyboard yang jelas.', 'sort_order' => 1, 'is_published' => true]
        );
        Material::query()->updateOrCreate(
            ['lesson_id' => $c3l1->id, 'title' => 'Panduan Ringkas Kontras & Fokus Keyboard'],
            ['course_id' => $course3->id, 'type' => 'text', 'content' => "1. Teks normal minimal kontras 4.5:1.\n2. Semua tombol interaktif wajib memiliki focus ring yang terlihat.\n3. Target sentuh minimal 40x40px.", 'sort_order' => 1, 'is_published' => true]
        );

        // Material progress for Student 1
        foreach ([$mat1, $mat2, $mat3] as $mat) {
            MaterialProgress::query()->updateOrCreate(
                ['material_id' => $mat->id, 'student_id' => $students[0]->id],
                ['is_completed' => true, 'completed_at' => now()->subDays(2)]
            );
        }

        // Assignments
        $assign1 = Assignment::query()->updateOrCreate(
            ['course_id' => $course1->id, 'title' => 'Tugas 1: Rancang Service Layer Modul Pendaftaran'],
            [
                'lesson_id' => $lesson1->id,
                'created_by' => $instructor1->id,
                'description' => 'Buat rancangan class diagram dan implementasi ringkas Service class untuk proses pendaftaran mahasiswa ke kelas dengan DB transaction.',
                'due_at' => now()->addDays(5),
                'max_score' => 100,
                'submission_type' => 'both',
                'allow_late_submission' => true,
                'is_published' => true,
            ]
        );

        $assign2 = Assignment::query()->updateOrCreate(
            ['course_id' => $course1->id, 'title' => 'Tugas 2: Implementasi Form Validasi & Policy'],
            [
                'lesson_id' => $lesson3->id,
                'created_by' => $instructor1->id,
                'description' => 'Tuliskan analisis mekanisme otorisasi berbasis Laravel Policy untuk membatasi akses materi antar kelas.',
                'due_at' => now()->addDays(10),
                'max_score' => 100,
                'submission_type' => 'text',
                'allow_late_submission' => false,
                'is_published' => true,
            ]
        );

        // Submissions
        Submission::query()->updateOrCreate(
            ['assignment_id' => $assign1->id, 'student_id' => $students[0]->id],
            [
                'text_answer' => 'Saya memisahkan EnrollmentService dengan DB::transaction untuk memastikan pencatatan enrollment dan notifikasi berjalan atomik.',
                'status' => 'graded',
                'score' => 90,
                'feedback' => 'Penjelasan sangat jelas dan struktur transaksi sudah tepat.',
                'submitted_at' => now()->subDays(3),
                'graded_at' => now()->subDays(2),
                'graded_by' => $instructor1->id,
            ]
        );

        Submission::query()->updateOrCreate(
            ['assignment_id' => $assign1->id, 'student_id' => $students[1]->id],
            [
                'text_answer' => 'Berikut rancangan EnrollmentService menggunakan repository pattern sederhana dan validasi kode kelas.',
                'status' => 'submitted',
                'submitted_at' => now()->subHours(12),
            ]
        );

        // Question Bank & Quiz / Exam
        $q1 = Question::query()->firstOrCreate(
            ['question_text' => 'Komponen Laravel manakah yang paling tepat untuk memvalidasi input request sebelum mencapai Controller?', 'created_by' => $instructor1->id],
            [
                'category_id' => $catWeb->id,
                'subject_id' => $subjWeb->id,
                'course_id' => $course1->id,
                'type' => 'multiple_choice',
                'explanation' => 'Form Request memisahkan aturan validasi dan otorisasi request dari logika controller.',
                'points' => 25,
                'difficulty' => 'easy',
                'is_bank' => true,
            ]
        );
        if ($q1->options()->count() === 0) {
            QuestionOption::insert([
                ['question_id' => $q1->id, 'option_text' => 'Form Request Class', 'is_correct' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['question_id' => $q1->id, 'option_text' => 'Blade View Template', 'is_correct' => false, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['question_id' => $q1->id, 'option_text' => 'Database Seeder', 'is_correct' => false, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['question_id' => $q1->id, 'option_text' => 'Migration Schema', 'is_correct' => false, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $q2 = Question::query()->firstOrCreate(
            ['question_text' => 'Eager loading menggunakan with() bertujuan untuk mencegah masalah N+1 query pada Eloquent.', 'created_by' => $instructor1->id],
            [
                'category_id' => $catWeb->id,
                'subject_id' => $subjWeb->id,
                'course_id' => $course1->id,
                'type' => 'true_false',
                'explanation' => 'with() memuat relasi secara batch sehingga tidak menjalankan query berulang per baris data.',
                'points' => 25,
                'difficulty' => 'easy',
                'is_bank' => true,
            ]
        );
        if ($q2->options()->count() === 0) {
            QuestionOption::insert([
                ['question_id' => $q2->id, 'option_text' => 'Benar', 'is_correct' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['question_id' => $q2->id, 'option_text' => 'Salah', 'is_correct' => false, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $q3 = Question::query()->firstOrCreate(
            ['question_text' => 'Jelaskan mengapa nilai role dan skor ujian tidak boleh dipercaya langsung dari input frontend.', 'created_by' => $instructor1->id],
            [
                'category_id' => $catWeb->id,
                'subject_id' => $subjWeb->id,
                'course_id' => $course1->id,
                'type' => 'essay',
                'explanation' => 'Payload HTTP dapat dimodifikasi oleh pengguna; seluruh otorisasi, timer, dan kalkulasi nilai wajib diverifikasi di server.',
                'points' => 50,
                'difficulty' => 'medium',
                'is_bank' => true,
            ]
        );

        $quiz1 = Quiz::query()->updateOrCreate(
            ['course_id' => $course1->id, 'title' => 'Kuis 1: Dasar Arsitektur & Keamanan Laravel'],
            [
                'lesson_id' => $lesson1->id,
                'created_by' => $instructor1->id,
                'description' => 'Evaluasi pemahaman mengenai Form Request, Eloquent eager loading, dan validasi server-side.',
                'type' => 'quiz',
                'duration_minutes' => 20,
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(14),
                'max_attempts' => 2,
                'passing_score' => 70,
                'shuffle_questions' => true,
                'shuffle_options' => true,
                'show_results' => true,
                'show_explanations' => true,
                'is_published' => true,
            ]
        );

        $quiz1->questions()->sync([
            $q1->id => ['sort_order' => 1, 'points' => 25],
            $q2->id => ['sort_order' => 2, 'points' => 25],
            $q3->id => ['sort_order' => 3, 'points' => 50],
        ]);

        // Completed Quiz Attempt for Student 1
        $q1CorrectOption = $q1->options()->where('is_correct', true)->first();
        $q2CorrectOption = $q2->options()->where('is_correct', true)->first();

        $attempt1 = QuizAttempt::query()->updateOrCreate(
            ['quiz_id' => $quiz1->id, 'student_id' => $students[0]->id, 'attempt_number' => 1],
            [
                'status' => 'graded',
                'question_order' => [$q1->id, $q2->id, $q3->id],
                'started_at' => now()->subDays(2)->subMinutes(15),
                'expires_at' => now()->subDays(2)->addMinutes(5),
                'submitted_at' => now()->subDays(2),
                'objective_score' => 50,
                'essay_score' => 40,
                'total_score' => 90,
                'percentage' => 90,
                'is_passed' => true,
                'needs_manual_grading' => false,
                'graded_by' => $instructor1->id,
                'graded_at' => now()->subDay(),
            ]
        );

        QuizAnswer::query()->updateOrCreate(
            ['quiz_attempt_id' => $attempt1->id, 'question_id' => $q1->id],
            ['selected_option_id' => $q1CorrectOption?->id, 'is_correct' => true, 'score_awarded' => 25, 'answered_at' => now()->subDays(2)]
        );
        QuizAnswer::query()->updateOrCreate(
            ['quiz_attempt_id' => $attempt1->id, 'question_id' => $q2->id],
            ['selected_option_id' => $q2CorrectOption?->id, 'is_correct' => true, 'score_awarded' => 25, 'answered_at' => now()->subDays(2)]
        );
        QuizAnswer::query()->updateOrCreate(
            ['quiz_attempt_id' => $attempt1->id, 'question_id' => $q3->id],
            ['essay_answer' => 'Karena request dari browser bisa dimanipulasi lewat DevTools atau HTTP client, sehingga server wajib memvalidasi hak akses, waktu ujian, dan menghitung nilai dari database.', 'score_awarded' => 40, 'feedback' => 'Jawaban tepat dan lengkap.', 'answered_at' => now()->subDays(2)]
        );

        // Seed matching published exam + graded attempt for Student 1 so Grade & Certificate stay consistent after recalculation
        $exam1 = Quiz::query()->updateOrCreate(
            ['course_id' => $course1->id, 'title' => 'Ujian Tengah Semester: Laravel & Inertia'],
            [
                'lesson_id' => $lesson1->id,
                'created_by' => $instructor1->id,
                'description' => 'Evaluasi menyeluruh arsitektur server-driven SPA dan otorisasi.',
                'type' => 'exam',
                'duration_minutes' => 60,
                'starts_at' => now()->subDays(4),
                'ends_at' => now()->addDays(14),
                'max_attempts' => 1,
                'passing_score' => 70,
                'shuffle_questions' => false,
                'shuffle_options' => false,
                'show_results' => true,
                'show_explanations' => true,
                'is_published' => true,
            ]
        );

        $exam1->questions()->sync([
            $q1->id => ['sort_order' => 1, 'points' => 50],
            $q2->id => ['sort_order' => 2, 'points' => 50],
        ]);

        $examAttempt1 = QuizAttempt::query()->updateOrCreate(
            ['quiz_id' => $exam1->id, 'student_id' => $students[0]->id, 'attempt_number' => 1],
            [
                'status' => 'graded',
                'question_order' => [$q1->id, $q2->id],
                'started_at' => now()->subDays(1)->subMinutes(35),
                'expires_at' => now()->subDays(1)->addMinutes(25),
                'submitted_at' => now()->subDays(1),
                'objective_score' => 86.25,
                'essay_score' => 0,
                'total_score' => 86.25,
                'percentage' => 86.25,
                'is_passed' => true,
                'needs_manual_grading' => false,
                'graded_by' => $instructor1->id,
                'graded_at' => now()->subDay(),
            ]
        );

        QuizAnswer::query()->updateOrCreate(
            ['quiz_attempt_id' => $examAttempt1->id, 'question_id' => $q1->id],
            ['selected_option_id' => $q1CorrectOption?->id, 'is_correct' => true, 'score_awarded' => 50, 'answered_at' => now()->subDay()]
        );

        // Grade & Certificate for Student 1
        Grade::query()->updateOrCreate(
            ['course_id' => $course1->id, 'student_id' => $students[0]->id],
            [
                'assignment_avg' => 90.00,
                'quiz_avg' => 90.00,
                'exam_avg' => 86.25,
                'final_score' => 88.50,
                'letter_grade' => 'A',
                'instructor_feedback' => 'Konsisten menyelesaikan seluruh materi dan tugas tepat waktu.',
                'updated_by' => $instructor1->id,
            ]
        );

        Certificate::query()->updateOrCreate(
            ['course_id' => $course1->id, 'student_id' => $students[0]->id],
            [
                'certificate_number' => 'VLMS-2026-0001',
                'final_score' => 88.50,
                'verification_hash' => hash('sha256', 'VLMS-2026-0001-' . $students[0]->id),
                'issued_at' => now()->subDay(),
            ]
        );

        // Announcements (Global + Course)
        Announcement::query()->updateOrCreate(
            ['title' => 'Pembukaan Perkuliahan Semester Ganjil 2026/2027', 'course_id' => null],
            [
                'author_id' => $admin->id,
                'content' => 'Selamat datang di tahun akademik baru. Pastikan seluruh mahasiswa bergabung ke kelas menggunakan kode kelas yang dibagikan dosen pengampu masing-masing.',
                'is_pinned' => true,
                'published_at' => now()->subDays(7),
            ]
        );

        Announcement::query()->updateOrCreate(
            ['title' => 'Jadwal Konsultasi Tugas Arsitektur Web', 'course_id' => $course1->id],
            [
                'author_id' => $instructor1->id,
                'content' => 'Konsultasi tugas Service Layer dibuka setiap hari Rabu pukul 13.00-15.00 WIB atau melalui forum diskusi kelas.',
                'is_pinned' => false,
                'published_at' => now()->subDays(2),
            ]
        );

        // Discussion & Comments
        $discussion = Discussion::query()->updateOrCreate(
            ['course_id' => $course1->id, 'title' => 'Diskusi Pertemuan 1: Kapan harus membuat Service Class baru?'],
            [
                'user_id' => $students[1]->id,
                'body' => 'Apakah setiap operasi CRUD sederhana harus dibuatkan service class terpisah, atau cukup untuk proses yang melibatkan transaksi multi-tabel?',
                'is_pinned' => false,
                'is_locked' => false,
            ]
        );

        Comment::query()->firstOrCreate(
            ['discussion_id' => $discussion->id, 'user_id' => $instructor1->id],
            [
                'body' => 'Pertanyaan bagus. Untuk CRUD tunggal yang sangat sederhana, controller dengan FormRequest sudah memadai. Service class wajib dipakai saat ada aturan bisnis kompleks, transaksi multi-tabel, atau logika yang digunakan ulang di beberapa tempat.',
            ]
        );

        // Activity Logs (multi-day for realistic dashboard chart)
        $activitySeeds = [
            ['action' => 'system.seeded', 'description' => 'Inisialisasi data awal VerdantLMS berhasil dijalankan.', 'user_id' => $admin->id, 'days_ago' => 6],
            ['action' => 'course.created', 'description' => 'Dr. Nadia Kusuma menerbitkan kelas Arsitektur Aplikasi Web Modern.', 'user_id' => $instructor1->id, 'days_ago' => 5],
            ['action' => 'enrollment.joined', 'description' => 'Siti Aisyah Putri bergabung ke kelas WEB2026.', 'user_id' => $students[0]->id, 'days_ago' => 4],
            ['action' => 'assignment.submitted', 'description' => 'Siti Aisyah Putri mengumpulkan Tugas 1: Rancang Service Layer.', 'user_id' => $students[0]->id, 'days_ago' => 3],
            ['action' => 'quiz.submitted', 'description' => 'Siti Aisyah Putri menyelesaikan Kuis 1 dengan skor 90%.', 'user_id' => $students[0]->id, 'days_ago' => 2],
            ['action' => 'instructor.submission_graded', 'description' => 'Dr. Nadia Kusuma menilai tugas Siti Aisyah Putri (90).', 'user_id' => $instructor1->id, 'days_ago' => 1],
            ['action' => 'quiz.submitted', 'description' => 'Dimas Pratama mengumpulkan Kuis 1 (menunggu penilaian essay).', 'user_id' => $students[1]->id, 'days_ago' => 0],
        ];
        foreach ($activitySeeds as $logItem) {
            $log = ActivityLog::query()->firstOrCreate(
                ['action' => $logItem['action'], 'description' => $logItem['description']],
                ['user_id' => $logItem['user_id'], 'properties' => ['demo' => true]]
            );
            $log->created_at = now()->subDays($logItem['days_ago']);
            $log->updated_at = now()->subDays($logItem['days_ago']);
            $log->save();
        }

        // Pending Essay Attempt for Student 2 on Quiz 1 (great for demoing Essay Grading)
        $attempt2 = QuizAttempt::query()->updateOrCreate(
            ['quiz_id' => $quiz1->id, 'student_id' => $students[1]->id, 'attempt_number' => 1],
            [
                'status' => 'submitted',
                'question_order' => [$q1->id, $q2->id, $q3->id],
                'started_at' => now()->subHours(2),
                'expires_at' => now()->subHours(1)->subMinutes(40),
                'submitted_at' => now()->subHours(1)->subMinutes(45),
                'objective_score' => 50,
                'essay_score' => 0,
                'total_score' => 50,
                'percentage' => 50,
                'is_passed' => false,
                'needs_manual_grading' => true,
            ]
        );
        QuizAnswer::query()->updateOrCreate(
            ['quiz_attempt_id' => $attempt2->id, 'question_id' => $q1->id],
            ['selected_option_id' => $q1CorrectOption?->id, 'is_correct' => true, 'score_awarded' => 25, 'answered_at' => now()->subHours(2)]
        );
        QuizAnswer::query()->updateOrCreate(
            ['quiz_attempt_id' => $attempt2->id, 'question_id' => $q2->id],
            ['selected_option_id' => $q2CorrectOption?->id, 'is_correct' => true, 'score_awarded' => 25, 'answered_at' => now()->subHours(2)]
        );
        QuizAnswer::query()->updateOrCreate(
            ['quiz_attempt_id' => $attempt2->id, 'question_id' => $q3->id],
            ['essay_answer' => 'Karena data dari client bisa diubah user melalui inspect element atau Postman, jadi server harus selalu menghitung ulang skor dan memeriksa hak akses.', 'answered_at' => now()->subHours(2)]
        );

        // Additional Bank Soal across all 3 subjects (IF-301, IF-204, DK-210)
        $extraQuestions = [
            // IF-301 (Web)
            [
                'subject_id' => $subjWeb->id, 'category_id' => $catWeb->id, 'course_id' => $course1->id, 'created_by' => $instructor1->id,
                'type' => 'multiple_choice', 'points' => 20, 'difficulty' => 'medium',
                'question_text' => 'Directive Vue 3 manakah yang digunakan untuk melakukan binding dua arah pada input form?',
                'explanation' => 'v-model menyediakan two-way binding antara state komponen dan elemen input.',
                'options' => [
                    ['v-model', true], ['v-bind', false], ['v-once', false], ['v-html', false],
                ],
            ],
            [
                'subject_id' => $subjWeb->id, 'category_id' => $catWeb->id, 'course_id' => $course1->id, 'created_by' => $instructor1->id,
                'type' => 'multiple_choice', 'points' => 20, 'difficulty' => 'hard',
                'question_text' => 'Method Laravel manakah yang paling tepat untuk membungkus beberapa operasi tulis database agar bersifat atomik?',
                'explanation' => 'DB::transaction() otomatis melakukan rollback jika terjadi exception di dalam closure.',
                'options' => [
                    ['DB::transaction()', true], ['DB::select()', false], ['Cache::remember()', false], ['Route::middleware()', false],
                ],
            ],
            [
                'subject_id' => $subjWeb->id, 'category_id' => $catWeb->id, 'course_id' => $course1->id, 'created_by' => $instructor1->id,
                'type' => 'true_false', 'points' => 15, 'difficulty' => 'easy',
                'question_text' => 'Inertia.js memungkinkan pembuatan SPA modern menggunakan routing dan controller Laravel tanpa harus membangun REST API terpisah.',
                'explanation' => 'Inertia bertindak sebagai perekat antara backend Laravel dan frontend Vue/React.',
                'options' => [['Benar', true], ['Salah', false]],
            ],
            // IF-204 (Database)
            [
                'subject_id' => $subjDb->id, 'category_id' => $catData->id, 'course_id' => $course2->id, 'created_by' => $instructor1->id,
                'type' => 'multiple_choice', 'points' => 25, 'difficulty' => 'easy',
                'question_text' => 'Bentuk normal manakah yang mensyaratkan setiap atribut non-kunci tidak memiliki ketergantungan transitif terhadap primary key?',
                'explanation' => 'Third Normal Form (3NF) menghilangkan ketergantungan transitif antar kolom non-kunci.',
                'options' => [
                    ['3NF (Third Normal Form)', true], ['1NF (First Normal Form)', false], ['Unnormalized Form', false], ['2NF (Second Normal Form)', false],
                ],
            ],
            [
                'subject_id' => $subjDb->id, 'category_id' => $catData->id, 'course_id' => $course2->id, 'created_by' => $instructor1->id,
                'type' => 'true_false', 'points' => 25, 'difficulty' => 'medium',
                'question_text' => 'Menambahkan index pada setiap kolom di tabel selalu meningkatkan performa operasi INSERT dan UPDATE.',
                'explanation' => 'Salah. Index mempercepat SELECT, namun memperlambat INSERT/UPDATE karena index harus ikut diperbarui.',
                'options' => [['Benar', false], ['Salah', true]],
            ],
            [
                'subject_id' => $subjDb->id, 'category_id' => $catData->id, 'course_id' => $course2->id, 'created_by' => $instructor1->id,
                'type' => 'multiple_choice', 'points' => 25, 'difficulty' => 'medium',
                'question_text' => 'Properti ACID manakah yang menjamin bahwa seluruh perubahan dalam transaksi tersimpan permanen setelah commit meskipun terjadi mati listrik?',
                'explanation' => 'Durability memastikan hasil transaksi yang sudah commit tetap bertahan.',
                'options' => [
                    ['Durability', true], ['Atomicity', false], ['Consistency', false], ['Isolation', false],
                ],
            ],
            [
                'subject_id' => $subjDb->id, 'category_id' => $catData->id, 'course_id' => $course2->id, 'created_by' => $instructor1->id,
                'type' => 'essay', 'points' => 25, 'difficulty' => 'hard',
                'question_text' => 'Jelaskan perbedaan antara INNER JOIN dan LEFT JOIN beserta contoh kasus penggunaannya pada LMS.',
                'explanation' => 'INNER JOIN hanya mengembalikan baris yang cocok di kedua tabel, sedangkan LEFT JOIN tetap menampilkan semua baris dari tabel kiri.',
                'options' => [],
            ],
            // DK-210 (UI/UX)
            [
                'subject_id' => $subjUx->id, 'category_id' => $catDesign->id, 'course_id' => $course3->id, 'created_by' => $instructor1->id,
                'type' => 'multiple_choice', 'points' => 25, 'difficulty' => 'easy',
                'question_text' => 'Berapa rasio kontras warna minimum untuk teks normal agar memenuhi standar WCAG 2.1 Level AA?',
                'explanation' => 'WCAG 2.1 Level AA mensyaratkan rasio kontras minimal 4.5:1 untuk teks berukuran normal.',
                'options' => [
                    ['4.5 : 1', true], ['2.0 : 1', false], ['3.0 : 1', false], ['1.5 : 1', false],
                ],
            ],
            [
                'subject_id' => $subjUx->id, 'category_id' => $catDesign->id, 'course_id' => $course3->id, 'created_by' => $instructor1->id,
                'type' => 'true_false', 'points' => 25, 'difficulty' => 'easy',
                'question_text' => 'Navigasi menggunakan tombol Tab pada keyboard merupakan salah satu aspek penting dalam pengujian aksesibilitas web.',
                'explanation' => 'Benar, pengguna screen reader dan keyboard-only bergantung pada urutan fokus Tab yang logis.',
                'options' => [['Benar', true], ['Salah', false]],
            ],
        ];

        $dbQuestions = [];
        foreach ($extraQuestions as $eq) {
            $qModel = Question::query()->firstOrCreate(
                ['question_text' => $eq['question_text'], 'created_by' => $eq['created_by']],
                [
                    'category_id' => $eq['category_id'],
                    'subject_id' => $eq['subject_id'],
                    'course_id' => $eq['course_id'],
                    'type' => $eq['type'],
                    'explanation' => $eq['explanation'],
                    'points' => $eq['points'],
                    'difficulty' => $eq['difficulty'],
                    'is_bank' => true,
                ]
            );
            if ($qModel->type !== 'essay' && $qModel->options()->count() === 0) {
                $rows = [];
                foreach ($eq['options'] as $idx => [$text, $correct]) {
                    $rows[] = [
                        'question_id' => $qModel->id,
                        'option_text' => $text,
                        'is_correct' => $correct,
                        'sort_order' => $idx + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                QuestionOption::insert($rows);
            }
            if ($eq['subject_id'] === $subjDb->id) {
                $dbQuestions[] = $qModel;
            }
        }

        // Playable Quiz on Course 2 (DB2026) so Student 1 can demo taking a live quiz + autosave immediately
        $quizDb = Quiz::query()->updateOrCreate(
            ['course_id' => $course2->id, 'title' => 'Kuis 1: Normalisasi & Transaksi Basis Data'],
            [
                'lesson_id' => $c2l1->id,
                'created_by' => $instructor2->id,
                'description' => 'Uji pemahaman mengenai normalisasi 1NF–3NF, properti ACID, dan strategi indexing.',
                'type' => 'quiz',
                'duration_minutes' => 30,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(21),
                'max_attempts' => 3,
                'passing_score' => 70,
                'shuffle_questions' => false,
                'shuffle_options' => true,
                'show_results' => true,
                'show_explanations' => true,
                'is_published' => true,
            ]
        );
        if ($dbQuestions !== []) {
            $syncDb = [];
            foreach ($dbQuestions as $idx => $dq) {
                $syncDb[$dq->id] = ['sort_order' => $idx + 1, 'points' => $dq->points];
            }
            $quizDb->questions()->sync($syncDb);
        }

        // Assignment on Course 2 for Student demo
        Assignment::query()->updateOrCreate(
            ['course_id' => $course2->id, 'title' => 'Tugas 1: Normalisasi Tabel Transaksi Kampus'],
            [
                'lesson_id' => $c2l1->id,
                'created_by' => $instructor2->id,
                'description' => 'Ubah skema tabel KRS yang belum ternormalisasi menjadi bentuk 3NF beserta penjelasan setiap tahapannya.',
                'due_at' => now()->addDays(7),
                'max_score' => 100,
                'submission_type' => 'both',
                'allow_late_submission' => true,
                'is_published' => true,
            ]
        );
    }
}
