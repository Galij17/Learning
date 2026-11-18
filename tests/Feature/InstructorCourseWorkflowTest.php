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
use App\Models\Subject;
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

    public function test_question_bank_supports_subject_directory_counts_and_unassigned_filtering(): void
    {
        $subject = Subject::create([
            'category_id' => $this->category->id,
            'code' => 'IF-404',
            'name' => 'Arsitektur Perangkat Lunak',
            'is_active' => true,
        ]);

        Question::create([
            'category_id' => $this->category->id,
            'subject_id' => $subject->id,
            'created_by' => $this->instructor->id,
            'type' => 'essay',
            'question_text' => 'Soal khusus mapel IF-404.',
            'points' => 10,
            'difficulty' => 'medium',
        ]);

        Question::create([
            'category_id' => $this->category->id,
            'subject_id' => null,
            'created_by' => $this->instructor->id,
            'type' => 'essay',
            'question_text' => 'Soal umum tanpa mapel.',
            'points' => 10,
            'difficulty' => 'easy',
        ]);

        $this->actingAs($this->instructor)
            ->get("/instructor/question-bank?subject_id={$subject->id}")
            ->assertOk()
            ->assertSee('Soal khusus mapel IF-404.')
            ->assertDontSee('Soal umum tanpa mapel.');

        $this->actingAs($this->instructor)
            ->get('/instructor/question-bank?subject_id=unassigned')
            ->assertOk()
            ->assertSee('Soal umum tanpa mapel.')
            ->assertDontSee('Soal khusus mapel IF-404.');
    }

    public function test_instructor_can_download_xlsx_template_import_questions_auto_pick_and_clone_quizzes(): void
    {
        $subject = Subject::create([
            'category_id' => $this->category->id,
            'code' => 'IF-501',
            'name' => 'Rekayasa Data',
            'is_active' => true,
        ]);

        $course = Course::create([
            'category_id' => $this->category->id,
            'subject_id' => $subject->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Rekayasa Data A',
            'slug' => 'rekayasa-data-a',
            'code' => 'RD101',
            'status' => 'published',
        ]);

        $quizA = Quiz::create([
            'course_id' => $course->id,
            'created_by' => $this->instructor->id,
            'title' => 'Kuis 1',
            'type' => 'quiz',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'passing_score' => 70,
        ]);

        $quizB = Quiz::create([
            'course_id' => $course->id,
            'created_by' => $this->instructor->id,
            'title' => 'Kuis 2 Paralel',
            'type' => 'quiz',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'passing_score' => 70,
        ]);

        // 1. Download template returns valid .xlsx streamed download
        $templateRes = $this->actingAs($this->instructor)->get('/instructor/question-bank/template');
        $templateRes->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $templateRes->headers->get('Content-Type'));
        $xlsxBinary = $templateRes->streamedContent();
        $this->assertNotEmpty($xlsxBinary);

        // Save streamed template bytes to temp UploadedFile -> verify template itself imports cleanly!
        $tempPath = tempnam(sys_get_temp_dir(), 'tmpl_') . '.xlsx';
        file_put_contents($tempPath, $xlsxBinary);
        $uploaded = new UploadedFile(
            $tempPath,
            'template-soal.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        try {
            // 2. Import template rows directly into Question Bank for the chosen subject
            $this->actingAs($this->instructor)
                ->post('/instructor/question-bank/import', [
                    'file' => $uploaded,
                    'category_id' => $this->category->id,
                    'subject_id' => $subject->id,
                ])
                ->assertRedirect();

            $this->assertSame(3, Question::where('subject_id', $subject->id)->where('created_by', $this->instructor->id)->count());

            // 3. Auto-pick 2 random questions from that subject into Quiz A
            $this->actingAs($this->instructor)
                ->post("/instructor/courses/{$course->id}/quizzes/{$quizA->id}/auto-pick", [
                    'count' => 2,
                    'subject_id' => $subject->id,
                ])
                ->assertRedirect();

            $this->assertSame(2, $quizA->questions()->count());

            // 4. Clone all questions from Quiz A into Quiz B
            $this->actingAs($this->instructor)
                ->post("/instructor/courses/{$course->id}/quizzes/{$quizB->id}/clone-questions", [
                    'source_quiz_id' => $quizA->id,
                ])
                ->assertRedirect();

            $this->assertSame(2, $quizB->questions()->count());

            // 5. Cross-instructor clone attempt must be forbidden
            $foreignCourse = Course::create([
                'category_id' => $this->category->id,
                'instructor_id' => $this->otherInstructor->id,
                'title' => 'Kelas Asing',
                'slug' => 'kelas-asing',
                'code' => 'KA999',
                'status' => 'published',
            ]);
            $foreignQuiz = Quiz::create([
                'course_id' => $foreignCourse->id,
                'created_by' => $this->otherInstructor->id,
                'title' => 'Ujian Asing',
                'type' => 'quiz',
                'duration_minutes' => 20,
                'max_attempts' => 1,
                'passing_score' => 70,
            ]);

            $this->actingAs($this->instructor)
                ->post("/instructor/courses/{$course->id}/quizzes/{$quizB->id}/clone-questions", [
                    'source_quiz_id' => $foreignQuiz->id,
                ])
                ->assertForbidden();
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_quiz_wizard_reorder_points_override_excel_preview_duplicate_and_bulk_actions(): void
    {
        $subjA = Subject::create(['category_id' => $this->category->id, 'code' => 'IF-101', 'name' => 'Dasar', 'is_active' => true]);
        $subjB = Subject::create(['category_id' => $this->category->id, 'code' => 'IF-102', 'name' => 'Lanjut', 'is_active' => true]);

        $course = Course::create([
            'category_id' => $this->category->id,
            'subject_id' => $subjA->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Kelas Wizard',
            'slug' => 'kelas-wizard',
            'code' => 'KW101',
            'status' => 'published',
        ]);

        // 1. Wizard create page renders
        $this->actingAs($this->instructor)->get("/instructor/courses/{$course->id}/quizzes/create")->assertOk();

        // Create 1 existing bank question
        $bankQ = Question::create([
            'category_id' => $this->category->id,
            'subject_id' => $subjA->id,
            'created_by' => $this->instructor->id,
            'type' => 'essay',
            'question_text' => 'Soal Bank Asli',
            'points' => 10,
            'difficulty' => 'easy',
        ]);

        // 2. Store quiz via wizard payload (existing bank question + new inline question)
        $this->actingAs($this->instructor)
            ->post("/instructor/courses/{$course->id}/quizzes", [
                'title' => 'Kuis Wizard 3 Langkah',
                'type' => 'quiz',
                'duration_minutes' => 25,
                'max_attempts' => 2,
                'passing_score' => 70,
                'is_published' => false,
                'question_ids' => [$bankQ->id],
                'new_questions' => [
                    [
                        'type' => 'essay',
                        'question_text' => 'Soal Baru Dari Wizard',
                        'points' => 15,
                        'difficulty' => 'medium',
                    ],
                ],
            ])
            ->assertRedirect();

        $quiz = Quiz::where('title', 'Kuis Wizard 3 Langkah')->firstOrFail();
        $this->assertFalse($quiz->is_published);
        $this->assertSame(2, $quiz->questions()->count());

        $secondQ = $quiz->questions()->where('question_text', 'Soal Baru Dari Wizard')->firstOrFail();

        // 3. Reorder questions (put secondQ first) & override points per quiz
        $this->actingAs($this->instructor)
            ->post("/instructor/courses/{$course->id}/quizzes/{$quiz->id}/reorder-questions", [
                'question_ids' => [$secondQ->id, $bankQ->id],
            ])
            ->assertRedirect();

        $this->assertSame($secondQ->id, $quiz->questions()->first()->id);

        $this->actingAs($this->instructor)
            ->patch("/instructor/courses/{$course->id}/quizzes/{$quiz->id}/questions/{$bankQ->id}/points", [
                'points' => 35,
            ])
            ->assertRedirect();

        $this->assertEquals(35.0, (float) $quiz->questions()->where('questions.id', $bankQ->id)->first()->pivot->points);
        $this->assertEquals(10.0, (float) $bankQ->fresh()->points);

        // 4. Duplicate & bulk move subject + bulk delete in Question Bank
        $this->actingAs($this->instructor)->post("/instructor/question-bank/{$bankQ->id}/duplicate")->assertRedirect();
        $copy = Question::where('question_text', 'Soal Bank Asli (Salinan)')->firstOrFail();

        $this->actingAs($this->instructor)
            ->post('/instructor/question-bank/bulk-action', [
                'action' => 'move_subject',
                'question_ids' => [$copy->id],
                'subject_id' => $subjB->id,
            ])
            ->assertRedirect();
        $this->assertSame($subjB->id, $copy->fresh()->subject_id);

        $this->actingAs($this->instructor)
            ->post('/instructor/question-bank/bulk-action', [
                'action' => 'delete',
                'question_ids' => [$copy->id],
            ])
            ->assertRedirect();
        $this->assertSoftDeleted('questions', ['id' => $copy->id]);

        // 5. Excel preview endpoint returns JSON preview without persisting
        $templateRes = $this->actingAs($this->instructor)->get('/instructor/question-bank/template');
        $tempPath = tempnam(sys_get_temp_dir(), 'prev_') . '.xlsx';
        file_put_contents($tempPath, $templateRes->streamedContent());
        $uploaded = new UploadedFile($tempPath, 'preview.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        try {
            $this->actingAs($this->instructor)
                ->postJson('/instructor/question-bank/import-preview', ['file' => $uploaded])
                ->assertOk()
                ->assertJsonPath('valid_count', 3)
                ->assertJsonPath('error_count', 0);
        } finally {
            @unlink($tempPath);
        }
    }
}
