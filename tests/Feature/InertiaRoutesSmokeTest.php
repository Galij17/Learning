<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InertiaRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(LmsDemoSeeder::class);
    }

    public function test_public_pages_render_ok(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_admin_pages_render_ok(): void
    {
        $admin = User::where('email', 'admin@verdantlms.test')->firstOrFail();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/categories')->assertOk();
        $this->actingAs($admin)->get('/admin/subjects')->assertOk();
        $this->actingAs($admin)->get('/admin/periods')->assertOk();
        $this->actingAs($admin)->get('/admin/courses')->assertOk();
        $this->actingAs($admin)->get('/admin/announcements')->assertOk();
        $this->actingAs($admin)->get('/admin/activity-logs')->assertOk();
        $this->actingAs($admin)->get('/admin/reports')->assertOk();
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
    }

    public function test_instructor_pages_render_ok(): void
    {
        $instructor = User::where('email', 'dosen@verdantlms.test')->firstOrFail();
        $course = $instructor->taughtCourses()->firstOrFail();
        $assignment = $course->assignments()->firstOrFail();
        $quiz = $course->quizzes()->firstOrFail();

        $this->actingAs($instructor)->get('/instructor/dashboard')->assertOk();
        $this->actingAs($instructor)->get('/instructor/courses')->assertOk();
        $this->actingAs($instructor)->get("/instructor/courses/{$course->id}")->assertOk();
        $this->actingAs($instructor)->get("/instructor/courses/{$course->id}/assignments/{$assignment->id}")->assertOk();
        $this->actingAs($instructor)->get("/instructor/courses/{$course->id}/quizzes/{$quiz->id}")->assertOk();
        $this->actingAs($instructor)->get("/instructor/courses/{$course->id}/gradebook")->assertOk();
        $this->actingAs($instructor)->get('/instructor/question-bank')->assertOk();
        $this->actingAs($instructor)->get("/courses/{$course->id}/discussions")->assertOk();
    }

    public function test_student_and_shared_pages_render_ok(): void
    {
        $student = User::where('email', 'siswa@verdantlms.test')->firstOrFail();
        $course = \App\Models\Course::query()
            ->whereHas('enrollments', fn ($q) => $q->where('student_id', $student->id))
            ->whereHas('materials')
            ->whereHas('assignments')
            ->whereHas('quizzes')
            ->firstOrFail();
        $material = $course->materials()->firstOrFail();
        $assignment = $course->assignments()->firstOrFail();
        $quiz = $course->quizzes()->firstOrFail();

        $this->actingAs($student)->get('/student/dashboard')->assertOk();
        $this->actingAs($student)->get('/student/courses')->assertOk();
        $this->actingAs($student)->get("/student/courses/{$course->id}")->assertOk();
        $this->actingAs($student)->get("/student/courses/{$course->id}/materials/{$material->id}")->assertOk();
        $this->actingAs($student)->get("/student/courses/{$course->id}/assignments/{$assignment->id}")->assertOk();
        $this->actingAs($student)->get("/student/courses/{$course->id}/quizzes/{$quiz->id}")->assertOk();
        $this->actingAs($student)->get('/student/grades')->assertOk();
        $this->actingAs($student)->get('/profile')->assertOk();
        $this->actingAs($student)->get('/notifications')->assertOk();
        $this->actingAs($student)->get('/certificates')->assertOk();
        $this->actingAs($student)->get("/courses/{$course->id}/discussions")->assertOk();
    }
}
