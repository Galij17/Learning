<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorCourseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private User $otherInstructor;
    private User $student;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $instRole = Role::where('name', 'instructor')->firstOrFail();
        $studRole = Role::where('name', 'student')->firstOrFail();

        $this->instructor = User::factory()->create(['is_active' => true]);
        $this->instructor->roles()->attach($instRole->id);

        $this->otherInstructor = User::factory()->create(['is_active' => true]);
        $this->otherInstructor->roles()->attach($instRole->id);

        $this->student = User::factory()->create(['is_active' => true]);
        $this->student->roles()->attach($studRole->id);

        $this->category = Category::create([
            'name' => 'Teknik Informatika',
            'slug' => 'teknik-informatika',
            'is_active' => true,
        ]);
    }

    public function test_instructor_can_create_course_lesson_material_and_grade_assignment(): void
    {
        Storage::fake('local');

        $this->actingAs($this->instructor)->post('/instructor/courses', [
            'title' => 'Rekayasa Perangkat Lunak',
            'category_id' => $this->category->id,
            'description' => 'Kelas RPL',
            'status' => 'published',
            'assignment_weight' => 40,
            'quiz_weight' => 30,
            'exam_weight' => 30,
            'min_completion_percentage' => 80,
            'min_passing_score' => 70,
            'certificate_enabled' => true,
        ])->assertRedirect();

        $course = Course::where('title', 'Rekayasa Perangkat Lunak')->firstOrFail();

        // Other instructor cannot modify this course
        $this->actingAs($this->otherInstructor)
            ->post("/instructor/courses/{$course->id}/lessons", [
                'title' => 'Bab Ilegal',
                'meeting_number' => 1,
            ])
            ->assertForbidden();

        // Owner creates lesson & uploads private PDF material
        $this->actingAs($this->instructor)
            ->post("/instructor/courses/{$course->id}/lessons", [
                'title' => 'Pertemuan 1: SDLC',
                'meeting_number' => 1,
                'is_published' => true,
            ])
            ->assertRedirect();

        $lesson = Lesson::where('course_id', $course->id)->firstOrFail();

        $this->actingAs($this->instructor)
            ->post("/instructor/courses/{$course->id}/materials", [
                'lesson_id' => $lesson->id,
                'title' => 'Slide Pengantar SDLC',
                'type' => 'pdf',
                'file' => UploadedFile::fake()->create('sdlc.pdf', 120, 'application/pdf'),
                'sort_order' => 1,
                'is_published' => true,
            ])
            ->assertRedirect();

        $material = Material::where('course_id', $course->id)->firstOrFail();
        Storage::disk('local')->assertExists($material->file_path);

        // Create assignment and grade a student's submission
        $this->actingAs($this->instructor)
            ->post("/instructor/courses/{$course->id}/assignments", [
                'title' => 'Analisis Kebutuhan',
                'description' => 'Buat dokumen SRS.',
                'max_score' => 100,
                'submission_type' => 'both',
                'allow_late_submission' => true,
                'is_published' => true,
            ])
            ->assertRedirect();

        $assignment = Assignment::where('course_id', $course->id)->firstOrFail();

        Enrollment::create([
            'course_id' => $course->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'text_answer' => 'Dokumen SRS selesai',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->instructor)
            ->post("/instructor/submissions/{$submission->id}/grade", [
                'score' => 92,
                'feedback' => 'Sangat terstruktur.',
            ])
            ->assertRedirect();

        $this->assertEquals(92.0, (float) $submission->fresh()->score);
        $this->assertSame('graded', $submission->fresh()->status);
    }

    public function test_cross_course_lesson_cross_owner_questions_and_scoped_course_search_are_enforced(): void
    {
        $myCourse = Course::create([
            'category_id' => $this->category->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'SharedKeywordPemrograman Saya',
            'slug' => 'shared-saya',
            'code' => 'SH001',
            'status' => 'published',
        ]);

        $otherCourse = Course::create([
            'category_id' => $this->category->id,
            'instructor_id' => $this->otherInstructor->id,
            'title' => 'SharedKeywordPemrograman Orang Lain',
            'slug' => 'shared-orang-lain',
            'code' => 'SH002',
            'status' => 'published',
        ]);
        $otherLesson = Lesson::create(['course_id' => $otherCourse->id, 'title' => 'Bab Asing', 'meeting_number' => 1, 'sort_order' => 1, 'is_published' => true]);
        $otherQuestion = Question::create([
            'course_id' => $otherCourse->id,
            'created_by' => $this->otherInstructor->id,
            'type' => 'essay',
            'question_text' => 'Soal rahasia dosen lain',
            'points' => 10,
            'difficulty' => 'easy',
        ]);

        // Cross-course lesson_id must fail validation
        $this->actingAs($this->instructor)
            ->post("/instructor/courses/{$myCourse->id}/materials", [
                'lesson_id' => $otherLesson->id,
                'title' => 'Materi Ilegal',
                'type' => 'text',
                'content' => 'Konten',
            ])
            ->assertSessionHasErrors('lesson_id');

        // Attaching another instructor's question_id must fail validation
        $quiz = Quiz::create([
            'course_id' => $myCourse->id,
            'created_by' => $this->instructor->id,
            'title' => 'Kuis Saya',
            'type' => 'quiz',
            'duration_minutes' => 15,
            'max_attempts' => 1,
            'passing_score' => 70,
        ]);

        $this->actingAs($this->instructor)
            ->post("/instructor/courses/{$myCourse->id}/quizzes/{$quiz->id}/attach-questions", [
                'question_ids' => [$otherQuestion->id],
            ])
            ->assertForbidden();

        // Scoped instructor course search must NOT leak otherInstructor's course
        $res = $this->actingAs($this->instructor)->get('/instructor/courses?search=SharedKeywordPemrograman');
        $res->assertOk();
        $res->assertSee('SharedKeywordPemrograman Saya');
        $res->assertDontSee('SharedKeywordPemrograman Orang Lain');
    }
}
