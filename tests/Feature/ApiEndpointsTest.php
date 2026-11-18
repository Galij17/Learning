<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $instructor;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $instRole = Role::where('name', 'instructor')->firstOrFail();
        $studRole = Role::where('name', 'student')->firstOrFail();

        $this->admin = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $this->instructor = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $this->instructor->roles()->attach($instRole->id);

        $this->student = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $this->student->roles()->attach($studRole->id);
    }

    public function test_auth_login_register_me_logout_and_inactive_guard(): void
    {
        // Bad credentials
        $this->postJson('/api/login', [
            'email' => $this->student->email,
            'password' => 'wrong-password',
        ])->assertStatus(422);

        // Inactive user blocked
        $inactive = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'is_active' => false,
        ]);
        $inactive->roles()->attach(Role::where('name', 'student')->firstOrFail()->id);

        $this->postJson('/api/login', [
            'email' => $inactive->email,
            'password' => 'Password123!',
        ])->assertStatus(403);

        // Register assigns student role
        $regRes = $this->postJson('/api/register', [
            'name' => 'Siswa Mobile',
            'email' => 'siswa.mobile@verdantlms.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '081234567890',
        ]);

        $regRes->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
        $this->assertSame('student', $regRes->json('user.role'));

        // Login valid
        $loginRes = $this->postJson('/api/login', [
            'email' => $this->student->email,
            'password' => 'Password123!',
            'device_name' => 'android-pixel',
        ]);

        $loginRes->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']]);

        $token = $loginRes->json('token');

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.email', $this->student->email);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        // Token revoked in DB
        $this->assertDatabaseCount('personal_access_tokens', 1); // only registration token remains
    }

    public function test_rbac_blocks_student_from_instructor_and_admin_endpoints(): void
    {
        Sanctum::actingAs($this->student);

        $this->getJson('/api/instructor/dashboard')->assertForbidden();
        $this->getJson('/api/admin/dashboard')->assertForbidden();

        Sanctum::actingAs($this->instructor);
        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }

    public function test_full_instructor_and_student_lms_flow(): void
    {
        // 1. Instructor creates course, lesson, material, assignment, question, quiz
        Sanctum::actingAs($this->instructor);

        $courseRes = $this->postJson('/api/instructor/courses', [
            'title' => 'Pemrograman Mobile Flutter',
            'category_name' => 'Mobile',
            'description' => 'Belajar Clean Architecture',
        ])->assertCreated();

        $courseId = $courseRes->json('data.id');
        $courseCode = $courseRes->json('data.code');
        $this->assertNotEmpty($courseCode);

        $lessonRes = $this->postJson("/api/instructor/courses/{$courseId}/lessons", [
            'title' => 'Bab 1 Pengenalan',
            'meeting_number' => 1,
        ])->assertCreated();
        $lessonId = $lessonRes->json('data.id');

        $materialRes = $this->post("/api/instructor/courses/{$courseId}/materials", [
            'lesson_id' => $lessonId,
            'title' => 'Modul PDF Bab 1',
            'type' => 'pdf',
            'file' => UploadedFile::fake()->create('bab1.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $materialId = $materialRes->json('data.id');

        $assignmentRes = $this->postJson("/api/instructor/courses/{$courseId}/assignments", [
            'title' => 'Tugas Wireframe',
            'description' => 'Unggah rancangan UI VerdantLMS',
            'max_score' => 100,
        ])->assertCreated();
        $assignmentId = $assignmentRes->json('data.id');

        $questionRes = $this->postJson('/api/instructor/question-bank', [
            'type' => 'multiple_choice',
            'question_text' => 'State management yang digunakan di VerdantLMS?',
            'points' => 10,
            'options' => [
                ['label' => 'A', 'option_text' => 'Riverpod', 'is_correct' => true],
                ['label' => 'B', 'option_text' => 'jQuery', 'is_correct' => false],
            ],
        ])->assertCreated();
        $questionId = $questionRes->json('data.id');

        $quizRes = $this->postJson("/api/instructor/courses/{$courseId}/quizzes", [
            'title' => 'Kuis Bab 1',
            'type' => 'quiz',
            'duration_minutes' => 30,
            'passing_score' => 70,
            'question_ids' => [$questionId],
        ])->assertCreated();
        $quizId = $quizRes->json('data.id');

        $this->getJson('/api/instructor/dashboard')->assertOk();
        $this->getJson('/api/instructor/question-bank')->assertOk();

        // 2. Student joins course, completes material, submits assignment, takes quiz
        Sanctum::actingAs($this->student);

        $this->postJson('/api/student/courses/join', [
            'code' => $courseCode,
        ])->assertCreated();

        $this->getJson('/api/student/dashboard')->assertOk();
        $this->getJson("/api/student/courses/{$courseId}")
            ->assertOk()
            ->assertJsonStructure(['course', 'lessons', 'assignments', 'quizzes', 'discussions']);

        $this->postJson("/api/student/courses/{$courseId}/materials/{$materialId}/complete")
            ->assertOk()
            ->assertJsonPath('progress_percentage', 100);

        $subRes = $this->post("/api/student/courses/{$courseId}/assignments/{$assignmentId}/submit", [
            'text_answer' => 'Ini jawaban tugas saya.',
            'file' => UploadedFile::fake()->create('tugas.pdf', 30, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
        $submissionId = $subRes->json('data.id');

        $startRes = $this->postJson("/api/student/courses/{$courseId}/quizzes/{$quizId}/start")
            ->assertOk();
        $attemptId = $startRes->json('data.id');
        $optionId = $startRes->json('data.questions.0.options.0.id');

        $this->postJson("/api/student/courses/{$courseId}/quizzes/{$quizId}/attempts/{$attemptId}/autosave", [
            'question_id' => $questionId,
            'selected_option_id' => $optionId,
        ])->assertOk();

        $this->postJson("/api/student/courses/{$courseId}/quizzes/{$quizId}/attempts/{$attemptId}/submit")
            ->assertOk();

        $this->getJson("/api/student/courses/{$courseId}/quizzes/{$quizId}/attempts/{$attemptId}/result")
            ->assertOk();

        // 3. Instructor grades submission & reads gradebook
        Sanctum::actingAs($this->instructor);

        $this->postJson("/api/instructor/submissions/{$submissionId}/grade", [
            'score' => 92,
            'feedback' => 'Struktur rapi dan jelas.',
        ])->assertOk();

        $this->getJson("/api/instructor/courses/{$courseId}/gradebook")->assertOk();

        // 4. Student checks grades, gamification, leaderboard
        Sanctum::actingAs($this->student);
        $this->getJson('/api/student/grades')->assertOk();
        $this->getJson('/api/student/gamification')->assertOk();
        $this->getJson('/api/student/leaderboard')->assertOk();
    }

    public function test_admin_management_and_reporting_endpoints(): void
    {
        Category::create(['name' => 'Matematika', 'slug' => 'matematika', 'is_active' => true]);

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['total_users', 'total_students', 'total_teachers', 'total_courses']);

        $this->getJson('/api/admin/users')->assertOk();

        $createdUser = $this->postJson('/api/admin/users', [
            'name' => 'Dosen Baru',
            'email' => 'dosen.baru@verdantlms.test',
            'role' => 'instructor',
            'password' => 'Password123!',
        ])->assertCreated();
        $userId = $createdUser->json('data.id');

        $this->patchJson("/api/admin/users/{$userId}/toggle-status")->assertOk();

        $this->postJson('/api/admin/announcements', [
            'title' => 'Pengumuman Sistem',
            'content' => 'Pemeliharaan server malam ini.',
        ])->assertCreated();

        $this->getJson('/api/admin/reports')->assertOk();
        $this->getJson('/api/admin/activity-logs')->assertOk();
    }

    public function test_api_security_guards_block_disabled_registration_unsafe_mimes_and_mid_attempt_results(): void
    {
        \App\Models\Setting::setValue('allow_registration', '0');

        $this->postJson('/api/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@verdantlms.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertForbidden();

        \App\Models\Setting::setValue('allow_registration', '1');

        $cat = Category::create(['name' => 'Keamanan', 'slug' => 'keamanan', 'is_active' => true]);
        Sanctum::actingAs($this->instructor);

        $courseRes = $this->postJson('/api/instructor/courses', [
            'category_id' => $cat->id,
            'title' => 'AppSec 101',
            'status' => 'published',
        ])->assertCreated();
        $courseId = $courseRes->json('data.id');

        $lessonId = $this->postJson("/api/instructor/courses/{$courseId}/lessons", [
            'title' => 'Bab 1',
            'meeting_number' => 1,
        ])->assertCreated()->json('data.id');

        // Reject unsafe HTML/SVG MIME uploads in API material store
        $this->post("/api/instructor/courses/{$courseId}/materials", [
            'lesson_id' => $lessonId,
            'title' => 'Payload HTML',
            'type' => 'pdf',
            'file' => UploadedFile::fake()->create('xss.html', 20, 'text/html'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();

        // Student cannot read mid-attempt result over API
        $questionId = $this->postJson('/api/instructor/question-bank', [
            'type' => 'true_false',
            'question_text' => 'Validasi input wajib di server.',
            'points' => 10,
            'options' => [
                ['label' => 'A', 'option_text' => 'Benar', 'is_correct' => true],
                ['label' => 'B', 'option_text' => 'Salah', 'is_correct' => false],
            ],
        ])->assertCreated()->json('data.id');

        $quizId = $this->postJson("/api/instructor/courses/{$courseId}/quizzes", [
            'title' => 'Kuis API',
            'type' => 'quiz',
            'duration_minutes' => 20,
            'passing_score' => 70,
            'is_published' => true,
            'question_ids' => [$questionId],
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->student);
        $this->postJson('/api/student/courses/join', ['code' => $courseRes->json('data.code')])->assertCreated();
        $attemptId = $this->postJson("/api/student/courses/{$courseId}/quizzes/{$quizId}/start")->assertOk()->json('data.id');

        $this->getJson("/api/student/courses/{$courseId}/quizzes/{$quizId}/attempts/{$attemptId}/result")
            ->assertForbidden();
    }
}
