<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Notifications\GradePublishedNotification;
use App\Services\ActivityLogService;
use App\Services\EnrollmentService;
use App\Services\FileStorageService;
use App\Services\GradingService;
use App\Support\ApiPayloadMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstructorApiController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
        private readonly GradingService $gradingService,
        private readonly EnrollmentService $enrollmentService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $courses = Course::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('instructor_id', $user->id))
            ->withCount('enrollments')
            ->get();
        $courseIds = $courses->pluck('id');

        $totalStudents = Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->where('status', '!=', 'dropped')
            ->distinct('student_id')
            ->count('student_id');

        $assignmentIds = Assignment::query()->whereIn('course_id', $courseIds)->pluck('id');
        $pendingReviews = Submission::query()
            ->whereIn('assignment_id', $assignmentIds)
            ->whereIn('status', ['submitted', 'late'])
            ->count();

        $upcomingExams = Quiz::query()
            ->with('course:id,title')
            ->withCount('questions')
            ->whereIn('course_id', $courseIds)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Quiz $q) => ApiPayloadMapper::quiz($q))
            ->values();

        $avgByCourse = Grade::query()
            ->whereIn('course_id', $courseIds)
            ->selectRaw('course_id, AVG(final_score) as avg_score')
            ->groupBy('course_id')
            ->pluck('avg_score', 'course_id');

        $classPerf = $courses->map(function (Course $c) use ($avgByCourse) {
            return [
                'class_code' => $c->code,
                'average_score' => round((float) ($avgByCourse[$c->id] ?? 0.0), 1),
            ];
        })->values();

        return response()->json([
            'total_classes' => $courses->count(),
            'total_students' => $totalStudents,
            'total_assignments' => $assignmentIds->count(),
            'pending_reviews' => $pendingReviews,
            'upcoming_exams' => $upcomingExams,
            'recent_activities' => $user->activityLogs()->latest()->limit(5)->pluck('description')->values(),
            'class_performance' => $classPerf,
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = $request->string('search')->toString();

        $courses = Course::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('instructor_id', $user->id))
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->withCount(['enrollments', 'materials'])
            ->with(['category:id,name', 'subject:id,name', 'instructor:id,name'])
            ->latest()
            ->get()
            ->map(fn (Course $c) => ApiPayloadMapper::course($c))
            ->values();

        return response()->json(['data' => $courses]);
    }

    public function storeCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $catName = trim($validated['category_name'] ?? 'Umum');
        $category = Category::query()->firstOrCreate(
            ['name' => $catName],
            ['slug' => Str::slug($catName) . '-' . Str::lower(Str::random(4))]
        );

        $course = Course::query()->create([
            'category_id' => $category->id,
            'instructor_id' => $request->user()->id,
            'title' => trim($validated['title']),
            'slug' => Str::slug($validated['title']) . '-' . Str::lower(Str::random(5)),
            'code' => strtoupper(Str::random(3)) . '-' . random_int(100, 999),
            'description' => $validated['description'] ?? null,
            'status' => 'published',
        ]);

        return response()->json(['data' => ApiPayloadMapper::course($course)], 201);
    }

    public function showCourse(Course $course): JsonResponse
    {
        Gate::authorize('update', $course);

        $students = $course->enrollments()
            ->with('student:id,name,email')
            ->get()
            ->map(fn (Enrollment $e) => [
                'student_id' => $e->student_id,
                'name' => $e->student?->name ?? '',
                'email' => $e->student?->email ?? '',
                'progress_percentage' => (float) $e->progress_percentage,
                'final_score' => $e->final_score !== null ? (float) $e->final_score : null,
            ])->values();

        return response()->json([
            'course' => ApiPayloadMapper::course($course),
            'students' => $students,
            'lessons' => $course->lessons()->with('materials')->get()->map(fn (Lesson $l) => ApiPayloadMapper::lesson($l))->values(),
            'assignments' => $course->assignments()->withCount('submissions')->get()->map(fn (Assignment $a) => ApiPayloadMapper::assignment($a))->values(),
            'quizzes' => $course->quizzes()->withCount('questions')->get()->map(fn (Quiz $q) => ApiPayloadMapper::quiz($q))->values(),
            'announcements' => $course->announcements()->get()->map(fn (Announcement $a) => ApiPayloadMapper::announcement($a))->values(),
        ]);
    }

    public function regenerateCode(Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $code = strtoupper(Str::random(3)) . '-' . random_int(100, 999);
        $course->update(['code' => $code]);

        return response()->json(['code' => $code]);
    }

    public function storeLesson(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'meeting_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'meeting_number' => $validated['meeting_number'] ?? 1,
            'sort_order' => $course->lessons()->count() + 1,
            'is_published' => true,
        ]);

        return response()->json(['data' => ApiPayloadMapper::lesson($lesson)], 201);
    }

    public function storeMaterial(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'lesson_id' => [
                'required',
                'integer',
                Rule::exists('lessons', 'id')->where('course_id', $course->id)->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:pdf,ppt,docx,image,video,link,text'],
            'content' => ['nullable', 'string', 'max:50000'],
            'video_url' => ['nullable', 'url:http,https', 'max:500'],
            'file' => ['nullable', 'file', 'max:51200', 'mimes:pdf,ppt,pptx,doc,docx,jpg,jpeg,png,webp,mp4,webm'],
        ]);

        if (in_array($validated['type'], ['pdf', 'ppt', 'docx', 'image'], true) && ! $request->hasFile('file')) {
            return response()->json(['message' => 'Berkas materi wajib diunggah untuk tipe materi ini.'], 422);
        }
        if ($validated['type'] === 'video' && ! $request->hasFile('file') && empty($validated['video_url'])) {
            return response()->json(['message' => 'Unggah berkas video atau masukkan URL video.'], 422);
        }
        if ($validated['type'] === 'link' && empty($validated['video_url'])) {
            return response()->json(['message' => 'Tautan referensi wajib diisi.'], 422);
        }
        if ($validated['type'] === 'text' && empty($validated['content'])) {
            return response()->json(['message' => 'Konten bacaan wajib diisi.'], 422);
        }

        $lesson = $course->lessons()->findOrFail($validated['lesson_id']);

        $fileMeta = [];
        if ($request->hasFile('file')) {
            $stored = $this->fileStorage->storePrivate($request->file('file'), "materials/{$course->id}");
            $fileMeta = [
                'storage_disk' => $stored['disk'],
                'file_path' => $stored['path'],
                'file_name' => $stored['name'],
                'mime_type' => $stored['mime'],
                'file_size' => $stored['size'],
            ];
        }

        $material = Material::query()->create(array_merge([
            'lesson_id' => $lesson->id,
            'course_id' => $course->id,
            'title' => trim($validated['title']),
            'type' => $validated['type'],
            'content' => $validated['content'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
            'is_published' => true,
        ], $fileMeta));

        return response()->json(['data' => ApiPayloadMapper::material($material)], 201);
    }

    public function destroyMaterial(Course $course, Material $material): JsonResponse
    {
        abort_unless((int) $material->course_id === (int) $course->id, 404);
        Gate::authorize('update', $course);

        $this->fileStorage->deleteIfExists($material->file_path, $material->storage_disk ?? 'local');
        $material->delete();

        return response()->json(['message' => 'Materi dihapus.']);
    }

    public function storeAssignment(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:20000'],
            'due_at' => ['nullable', 'date'],
            'max_score' => ['nullable', 'numeric', 'min:1', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png'],
        ]);

        $attachmentMeta = [];
        if ($request->hasFile('attachment')) {
            $stored = $this->fileStorage->storePrivate($request->file('attachment'), "assignments/{$course->id}");
            $attachmentMeta = [
                'attachment_path' => $stored['path'],
                'attachment_name' => $stored['name'],
            ];
        }

        $assignment = Assignment::query()->create(array_merge([
            'course_id' => $course->id,
            'created_by' => $request->user()->id,
            'title' => trim($validated['title']),
            'description' => trim($validated['description']),
            'due_at' => $validated['due_at'] ?? now()->addDays(7),
            'max_score' => $validated['max_score'] ?? 100,
            'is_published' => true,
        ], $attachmentMeta));

        return response()->json(['data' => ApiPayloadMapper::assignment($assignment)], 201);
    }

    public function submissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $courseIds = Course::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('instructor_id', $user->id))
            ->pluck('id');
        $assignmentIds = Assignment::query()->whereIn('course_id', $courseIds)->pluck('id');

        $subs = Submission::query()
            ->with('student:id,name')
            ->whereIn('assignment_id', $assignmentIds)
            ->latest('submitted_at')
            ->get()
            ->map(fn (Submission $s) => ApiPayloadMapper::submission($s))
            ->values();

        return response()->json(['data' => $subs]);
    }

    public function gradeSubmission(Request $request, Submission $submission): JsonResponse
    {
        $submission->loadMissing(['assignment.course', 'student']);
        Gate::authorize('manage', $submission->assignment);

        $maxScore = (float) ($submission->assignment?->max_score ?? 100);
        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', "max:{$maxScore}"],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update([
            'score' => $validated['score'],
            'feedback' => $validated['feedback'] ?? null,
            'status' => 'graded',
            'graded_at' => now(),
            'graded_by' => $request->user()->id,
        ]);

        $course = $submission->assignment->course;
        $this->gradingService->recalculateStudentGrade($course, $submission->student, $request->user());
        $this->enrollmentService->issueCertificateIfEligible($course, $submission->student);

        $submission->student->notify(new GradePublishedNotification(
            $course,
            $submission->assignment->title,
            (float) $submission->score,
            route('student.courses.show', $course->id)
        ));

        $this->activityLogService->log(
            'instructor.submission_graded',
            "Menilai tugas {$submission->assignment->title} untuk {$submission->student->name} ({$submission->score}).",
            $submission
        );

        return response()->json(['data' => ApiPayloadMapper::submission($submission->fresh())]);
    }

    public function questionBank(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->string('type')->toString();

        $items = Question::query()
            ->with('options')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest()
            ->get()
            ->map(fn (Question $q) => ApiPayloadMapper::question($q, true))
            ->values();

        return response()->json(['data' => $items]);
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:multiple_choice,true_false,essay'],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'numeric', 'min:1'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'explanation' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
        ]);

        $question = DB::transaction(function () use ($request, $validated) {
            $question = Question::query()->create([
                'created_by' => $request->user()->id,
                'type' => $validated['type'],
                'question_text' => trim($validated['question_text']),
                'points' => $validated['points'],
                'difficulty' => $validated['difficulty'] ?? 'medium',
                'explanation' => $validated['explanation'] ?? null,
                'is_bank' => true,
            ]);

            foreach ($validated['options'] ?? [] as $index => $opt) {
                $question->options()->create([
                    'option_text' => $opt['option_text'],
                    'is_correct' => (bool) $opt['is_correct'],
                    'sort_order' => $index + 1,
                ]);
            }

            return $question;
        });

        return response()->json(['data' => ApiPayloadMapper::question($question->fresh('options'), true)], 201);
    }

    public function storeQuiz(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:quiz,exam'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['integer', 'exists:questions,id,deleted_at,NULL'],
        ]);

        $questionIds = $validated['question_ids'] ?? [];
        if ($questionIds !== [] && ! $request->user()->isAdmin()) {
            $allowedCount = Question::query()
                ->whereIn('id', $questionIds)
                ->where(fn ($q) => $q->where('created_by', $request->user()->id)->orWhere('course_id', $course->id))
                ->count();
            if ($allowedCount !== count(array_unique($questionIds))) {
                return response()->json(['message' => 'Sebagian soal yang dipilih bukan milik Anda atau kelas ini.'], 403);
            }
        }

        $quiz = DB::transaction(function () use ($request, $course, $validated, $questionIds) {
            $quiz = Quiz::query()->create([
                'course_id' => $course->id,
                'created_by' => $request->user()->id,
                'title' => trim($validated['title']),
                'type' => $validated['type'],
                'duration_minutes' => $validated['duration_minutes'],
                'passing_score' => $validated['passing_score'],
                'shuffle_questions' => (bool) ($validated['shuffle_questions'] ?? false),
                'is_published' => true,
            ]);

            if ($questionIds !== []) {
                $questions = Question::query()->whereIn('id', $questionIds)->get()->keyBy('id');
                $syncData = [];
                foreach (array_values($questionIds) as $index => $qid) {
                    $q = $questions->get($qid);
                    if ($q) {
                        $syncData[$qid] = [
                            'sort_order' => $index + 1,
                            'points' => $q->points,
                        ];
                    }
                }
                $quiz->questions()->sync($syncData);
            }

            return $quiz;
        });

        $quiz->loadCount('questions');

        return response()->json(['data' => ApiPayloadMapper::quiz($quiz)], 201);
    }

    public function gradebook(Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $grades = $course->grades()->with('student:id,name')->get()->map(fn (Grade $g) => ApiPayloadMapper::grade($g))->values();

        return response()->json(['data' => $grades]);
    }

    public function updateGradeFeedback(Request $request, Course $course, int $studentId): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate(['instructor_feedback' => ['required', 'string', 'max:3000']]);
        $student = $course->students()->where('users.id', $studentId)->firstOrFail();

        $grade = $this->gradingService->recalculateStudentGrade(
            $course,
            $student,
            $request->user(),
            trim($validated['instructor_feedback'])
        );

        return response()->json(['data' => ApiPayloadMapper::grade($grade)]);
    }

    public function recalculateGradebook(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $this->gradingService->recalculateCourseGrades($course, $request->user());

        return response()->json(['message' => 'Gradebook berhasil dihitung ulang.']);
    }

    public function storeAnnouncement(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $ann = Announcement::query()->create([
            'course_id' => $course->id,
            'author_id' => $request->user()->id,
            'title' => trim($validated['title']),
            'content' => trim($validated['content']),
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'published_at' => now(),
        ]);

        return response()->json(['data' => ApiPayloadMapper::announcement($ann)], 201);
    }
}
