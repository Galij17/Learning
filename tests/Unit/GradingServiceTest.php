<?php

namespace Tests\Unit;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\Submission;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_weighted_final_grade_and_letter_grade_are_computed_correctly(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $role = Role::create(['name' => 'student', 'display_name' => 'Student']);
        $student->roles()->attach($role->id);

        $category = Category::create(['name' => 'Informatika', 'slug' => 'informatika', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'instructor_id' => $instructor->id,
            'title' => 'Algoritma Lanjut',
            'slug' => 'algoritma-lanjut',
            'code' => 'ALG101',
            'status' => 'published',
            'assignment_weight' => 30,
            'quiz_weight' => 30,
            'exam_weight' => 40,
            'min_completion_percentage' => 80,
            'min_passing_score' => 70,
            'certificate_enabled' => true,
        ]);

        $enrollment = Enrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 90,
            'enrolled_at' => now(),
        ]);

        // Assignment: 90 / 100 = 90%
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $instructor->id,
            'title' => 'Tugas 1',
            'description' => 'Selesaikan soal',
            'max_score' => 100,
            'submission_type' => 'both',
            'is_published' => true,
        ]);
        Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'text_answer' => 'Jawaban saya',
            'status' => 'graded',
            'score' => 90,
            'submitted_at' => now(),
            'graded_at' => now(),
        ]);

        // Quiz: best attempt 80%
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'created_by' => $instructor->id,
            'title' => 'Kuis 1',
            'type' => 'quiz',
            'duration_minutes' => 30,
            'max_attempts' => 2,
            'passing_score' => 70,
            'is_published' => true,
        ]);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now(),
            'objective_score' => 80,
            'essay_score' => 0,
            'total_score' => 80,
            'percentage' => 80,
            'is_passed' => true,
        ]);

        // Exam: 85%
        $exam = Quiz::create([
            'course_id' => $course->id,
            'created_by' => $instructor->id,
            'title' => 'UAS',
            'type' => 'exam',
            'duration_minutes' => 60,
            'max_attempts' => 1,
            'passing_score' => 70,
            'is_published' => true,
        ]);
        QuizAttempt::create([
            'quiz_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'started_at' => now()->subMinutes(50),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now(),
            'objective_score' => 85,
            'essay_score' => 0,
            'total_score' => 85,
            'percentage' => 85,
            'is_passed' => true,
        ]);

        /** @var GradingService $service */
        $service = app(GradingService::class);
        $grade = $service->recalculateStudentGrade($course, $student, $instructor, 'Pertahankan prestasi!');

        // Expected: 90*0.3 + 80*0.3 + 85*0.4 = 27 + 24 + 34 = 85.0
        $this->assertEquals(90.0, (float) $grade->assignment_avg);
        $this->assertEquals(80.0, (float) $grade->quiz_avg);
        $this->assertEquals(85.0, (float) $grade->exam_avg);
        $this->assertEquals(85.0, (float) $grade->final_score);
        $this->assertSame('A', $grade->letter_grade);
        $this->assertSame('Pertahankan prestasi!', $grade->instructor_feedback);

        $enrollment->refresh();
        $this->assertEquals(85.0, (float) $enrollment->final_score);
    }
}
