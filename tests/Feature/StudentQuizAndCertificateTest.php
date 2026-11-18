<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Certificate;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentQuizAndCertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private User $student;
    private User $outsider;
    private Course $course;
    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $instRole = Role::where('name', 'instructor')->firstOrFail();
        $studRole = Role::where('name', 'student')->firstOrFail();

        $this->instructor = User::factory()->create(['is_active' => true]);
        $this->instructor->roles()->attach($instRole->id);

        $this->student = User::factory()->create(['is_active' => true]);
        $this->student->roles()->attach($studRole->id);

        $this->outsider = User::factory()->create(['is_active' => true]);
        $this->outsider->roles()->attach($studRole->id);

        $cat = Category::create(['name' => 'Web', 'slug' => 'web', 'is_active' => true]);
        $this->course = Course::create([
            'category_id' => $cat->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Laravel & Vue',
            'slug' => 'laravel-vue',
            'code' => 'LV2026',
            'status' => 'published',
            'assignment_weight' => 0,
            'quiz_weight' => 0,
            'exam_weight' => 100,
            'min_completion_percentage' => 100,
            'min_passing_score' => 75,
            'certificate_enabled' => true,
        ]);

        $lesson = Lesson::create([
            'course_id' => $this->course->id,
            'title' => 'Pengenalan',
            'meeting_number' => 1,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $pdfPath = UploadedFile::fake()->create('modul.pdf', 100, 'application/pdf')->store('materials', 'local');
        $this->material = Material::create([
            'lesson_id' => $lesson->id,
            'course_id' => $this->course->id,
            'title' => 'Modul Privat',
            'type' => 'pdf',
            'file_path' => $pdfPath,
            'file_name' => 'modul.pdf',
            'mime_type' => 'application/pdf',
            'sort_order' => 1,
            'is_published' => true,
        ]);
    }

    public function test_private_material_cannot_be_downloaded_by_unenrolled_student(): void
    {
        $this->actingAs($this->outsider)
            ->get("/materials/{$this->material->id}/download")
            ->assertForbidden();
    }

    public function test_full_student_exam_autosave_manual_essay_and_certificate_flow(): void
    {
        // 1. Join course using code
        $this->actingAs($this->student)
            ->post('/student/courses/join', ['code' => 'lv2026'])
            ->assertRedirect("/student/courses/{$this->course->id}");

        // Enrolled student CAN download private material and complete it
        $this->actingAs($this->student)
            ->get("/materials/{$this->material->id}/download")
            ->assertOk();

        $this->actingAs($this->student)
            ->post("/student/courses/{$this->course->id}/materials/{$this->material->id}/complete")
            ->assertRedirect();

        // 2. Build exam with 1 MCQ (50 pts) + 1 Essay (50 pts)
        $exam = Quiz::create([
            'course_id' => $this->course->id,
            'created_by' => $this->instructor->id,
            'title' => 'Ujian Akhir',
            'type' => 'exam',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'passing_score' => 75,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'show_results' => true,
            'is_published' => true,
        ]);

        $mcq = Question::create([
            'course_id' => $this->course->id,
            'created_by' => $this->instructor->id,
            'type' => 'multiple_choice',
            'question_text' => 'Apa ORM bawaan Laravel?',
            'points' => 50,
            'difficulty' => 'easy',
        ]);
        $optCorrect = QuestionOption::create(['question_id' => $mcq->id, 'option_text' => 'Eloquent', 'is_correct' => true, 'sort_order' => 1]);
        QuestionOption::create(['question_id' => $mcq->id, 'option_text' => 'Doctrine', 'is_correct' => false, 'sort_order' => 2]);

        $essay = Question::create([
            'course_id' => $this->course->id,
            'created_by' => $this->instructor->id,
            'type' => 'essay',
            'question_text' => 'Jelaskan konsep Policy di Laravel.',
            'points' => 50,
            'difficulty' => 'medium',
        ]);

        $exam->questions()->attach([
            $mcq->id => ['sort_order' => 1, 'points' => 50],
            $essay->id => ['sort_order' => 2, 'points' => 50],
        ]);

        // 3. Student starts exam
        $this->actingAs($this->student)
            ->post("/student/courses/{$this->course->id}/quizzes/{$exam->id}/start")
            ->assertRedirect();

        $attempt = QuizAttempt::where('quiz_id', $exam->id)->where('student_id', $this->student->id)->firstOrFail();
        $this->assertCount(2, $attempt->question_order);

        // 4. Autosave MCQ + Essay answers
        $this->actingAs($this->student)
            ->postJson("/student/courses/{$this->course->id}/quizzes/{$exam->id}/attempts/{$attempt->id}/autosave", [
                'question_id' => $mcq->id,
                'selected_option_id' => $optCorrect->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['saved_at', 'remaining_seconds']);

        $this->actingAs($this->student)
            ->postJson("/student/courses/{$this->course->id}/quizzes/{$exam->id}/attempts/{$attempt->id}/autosave", [
                'question_id' => $essay->id,
                'essay_answer' => 'Policy mengatur otorisasi berbasis model.',
            ])
            ->assertOk();

        // 5. Submit exam -> objective graded (50), pending manual essay
        $this->actingAs($this->student)
            ->post("/student/courses/{$this->course->id}/quizzes/{$exam->id}/attempts/{$attempt->id}/submit")
            ->assertRedirect();

        $attempt->refresh();
        $this->assertEquals(50.0, (float) $attempt->objective_score);
        $this->assertTrue($attempt->needs_manual_grading);

        // 6. Instructor grades essay (45/50 -> total 95/100 -> 95% -> triggers auto certificate!)
        $essayAnswer = $attempt->answers()->where('question_id', $essay->id)->firstOrFail();

        $this->actingAs($this->instructor)
            ->post("/instructor/courses/{$this->course->id}/quizzes/{$exam->id}/attempts/{$attempt->id}/grade-essays", [
                'grades' => [
                    [
                        'answer_id' => $essayAnswer->id,
                        'score' => 45,
                        'feedback' => 'Tepat sekali.',
                    ],
                ],
            ])
            ->assertRedirect();

        $attempt->refresh();
        $this->assertFalse($attempt->needs_manual_grading);
        $this->assertEquals(95.0, (float) $attempt->percentage);
        $this->assertTrue($attempt->is_passed);

        // Certificate issued and public verification route works
        $certificate = Certificate::where('course_id', $this->course->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($certificate);

        $this->get("/certificates/verify/{$certificate->verification_hash}")->assertOk();
    }

    public function test_expired_quiz_attempt_is_auto_submitted_when_accessed(): void
    {
        $this->actingAs($this->student)->post('/student/courses/join', ['code' => 'LV2026']);

        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'created_by' => $this->instructor->id,
            'title' => 'Kuis Cepat',
            'type' => 'quiz',
            'duration_minutes' => 10,
            'max_attempts' => 1,
            'passing_score' => 70,
            'is_published' => true,
        ]);

        $mcq = Question::create([
            'course_id' => $this->course->id,
            'created_by' => $this->instructor->id,
            'type' => 'true_false',
            'question_text' => 'PHP berjalan di server.',
            'points' => 100,
            'difficulty' => 'easy',
        ]);
        QuestionOption::create(['question_id' => $mcq->id, 'option_text' => 'Benar', 'is_correct' => true, 'sort_order' => 1]);
        QuestionOption::create(['question_id' => $mcq->id, 'option_text' => 'Salah', 'is_correct' => false, 'sort_order' => 2]);
        $quiz->questions()->attach($mcq->id, ['sort_order' => 1, 'points' => 100]);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'question_order' => [$mcq->id],
            'option_orders' => [$mcq->id => $mcq->options->pluck('id')->all()],
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($this->student)
            ->get("/student/courses/{$this->course->id}/quizzes/{$quiz->id}/attempts/{$attempt->id}")
            ->assertRedirect("/student/courses/{$this->course->id}/quizzes/{$quiz->id}/attempts/{$attempt->id}/result");

        $this->assertSame('graded', $attempt->fresh()->status);
        $this->assertNotNull($attempt->fresh()->submitted_at);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'quiz.auto_submitted',
            'user_id' => $this->student->id,
        ]);
    }

    public function test_student_cannot_view_result_while_attempt_is_in_progress(): void
    {
        $this->actingAs($this->student)->post('/student/courses/join', ['code' => 'LV2026']);

        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'created_by' => $this->instructor->id,
            'title' => 'Ujian Berlangsung',
            'type' => 'quiz',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'passing_score' => 70,
            'is_published' => true,
        ]);

        $mcq = Question::create([
            'course_id' => $this->course->id,
            'created_by' => $this->instructor->id,
            'type' => 'true_false',
            'question_text' => 'HTTPS terenkripsi.',
            'points' => 10,
            'difficulty' => 'easy',
        ]);
        $quiz->questions()->attach($mcq->id, ['sort_order' => 1, 'points' => 10]);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'question_order' => [$mcq->id],
            'option_orders' => [],
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->actingAs($this->student)
            ->get("/student/courses/{$this->course->id}/quizzes/{$quiz->id}/attempts/{$attempt->id}/result")
            ->assertRedirect("/student/courses/{$this->course->id}/quizzes/{$quiz->id}/attempts/{$attempt->id}");
    }

    public function test_empty_essay_attempt_creates_gradeable_rows_and_rejects_excessive_score(): void
    {
        $this->actingAs($this->student)->post('/student/courses/join', ['code' => 'LV2026']);

        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'created_by' => $this->instructor->id,
            'title' => 'Ujian Essay Kosong',
            'type' => 'exam',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'passing_score' => 70,
            'is_published' => true,
        ]);

        $essay = Question::create([
            'course_id' => $this->course->id,
            'created_by' => $this->instructor->id,
            'type' => 'essay',
            'question_text' => 'Jelaskan konsep SOLID.',
            'points' => 25,
            'difficulty' => 'medium',
        ]);
        $quiz->questions()->attach($essay->id, ['sort_order' => 1, 'points' => 25]);

        // Start attempt and submit immediately without answering essay
        $this->actingAs($this->student)
            ->post("/student/courses/{$this->course->id}/quizzes/{$quiz->id}/start")
            ->assertRedirect();

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $this->student->id)->firstOrFail();

        $this->actingAs($this->student)
            ->post("/student/courses/{$this->course->id}/quizzes/{$quiz->id}/attempts/{$attempt->id}/submit")
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $essay->id,
        ]);

        $answerId = $attempt->answers()->firstOrFail()->id;

        // Score above max_pts (25) must fail validation / reject
        $this->actingAs($this->instructor)
            ->post("/instructor/courses/{$this->course->id}/quizzes/{$quiz->id}/attempts/{$attempt->id}/grade-essays", [
                'grades' => [
                    ['answer_id' => $answerId, 'score' => 999, 'feedback' => 'Terlalu tinggi'],
                ],
            ])
            ->assertSessionHasErrors();
    }

    public function test_private_stream_adds_nosniff_and_cross_discussion_parent_id_is_rejected(): void
    {
        $this->actingAs($this->student)->post('/student/courses/join', ['code' => 'LV2026']);

        $unsafePath = UploadedFile::fake()->create('page.html', 10, 'text/html')->store('materials', 'local');
        $unsafeMaterial = Material::create([
            'lesson_id' => $this->material->lesson_id,
            'course_id' => $this->course->id,
            'title' => 'HTML File',
            'type' => 'pdf',
            'file_path' => $unsafePath,
            'file_name' => 'page.html',
            'mime_type' => 'text/html',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $streamRes = $this->actingAs($this->student)->get("/materials/{$unsafeMaterial->id}/stream");
        $streamRes->assertOk();
        $this->assertSame('nosniff', $streamRes->headers->get('X-Content-Type-Options'));
        $this->assertStringStartsWith('attachment', (string) $streamRes->headers->get('Content-Disposition'));

        $d1 = Discussion::create(['course_id' => $this->course->id, 'user_id' => $this->instructor->id, 'title' => 'Diskusi 1', 'body' => 'Body 1']);
        $d2 = Discussion::create(['course_id' => $this->course->id, 'user_id' => $this->instructor->id, 'title' => 'Diskusi 2', 'body' => 'Body 2']);
        $foreignComment = Comment::create(['discussion_id' => $d2->id, 'user_id' => $this->instructor->id, 'body' => 'Komentar lain']);

        $this->actingAs($this->student)
            ->post("/courses/{$this->course->id}/discussions/{$d1->id}/comments", [
                'body' => 'Percobaan parent lintas diskusi',
                'parent_id' => $foreignComment->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }
}
