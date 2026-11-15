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
        $c2m1 = Material::query()->updateOrCreate(
            ['lesson_id' => $c2l1->id, 'title' => 'Panduan Normalisasi 1NF hingga BCNF'],
            ['course_id' => $course2->id, 'type' => 'text', 'content' => 'Setiap atribut non-kunci harus bergantung penuh hanya pada kunci kandidat.', 'sort_order' => 1, 'is_published' => true]
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

        // Activity Log
        ActivityLog::query()->firstOrCreate(
            ['action' => 'system.seeded', 'description' => 'Inisialisasi data awal VerdantLMS berhasil dijalankan.'],
            ['user_id' => $admin->id, 'properties' => ['courses' => 3, 'users' => 8]]
        );
    }
}
