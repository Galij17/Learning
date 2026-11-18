<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Submission;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\EnrollmentService;
use App\Services\FileStorageService;
use App\Services\QuizAttemptService;
use App\Support\ApiPayloadMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class StudentApiController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollmentService,
        private readonly QuizAttemptService $quizAttemptService,
        private readonly FileStorageService $fileStorage,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $student = $request->user();

        $enrollments = Enrollment::query()
            ->whereHas('course')
            ->with([
                'course' => fn ($q) => $q->withCount(['enrollments', 'materials'])
                    ->with(['category:id,name', 'subject:id,name', 'instructor:id,name']),
            ])
            ->where('student_id', $student->id)
            ->where('status', '!=', 'dropped')
            ->latest('updated_at')
            ->get();

        $courseIds = $enrollments->pluck('course_id');

        $assignments = Assignment::query()
            ->with('course:id,title')
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->orderBy('due_at')
            ->get();

        $submissionsByAssignment = Submission::query()
            ->where('student_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        $pendingAssignments = $assignments->filter(function (Assignment $a) use ($submissionsByAssignment) {
            return ! $submissionsByAssignment->has($a->id);
        })->values();

        $upcomingExams = Quiz::query()
            ->with('course:id,title')
            ->withCount('questions')
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->orderBy('ends_at')
            ->limit(5)
            ->get();

        $avgScore = (float) (Grade::query()
            ->whereHas('course')
            ->where('student_id', $student->id)
            ->avg('final_score') ?? 0.0);

        $since = Carbon::now()->subDays(6)->startOfDay();
        $completionsByDay = MaterialProgress::query()
            ->where('student_id', $student->id)
            ->where('completed_at', '>=', $since)
            ->selectRaw('DATE(completed_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
        $subsByDay = Submission::query()
            ->where('student_id', $student->id)
            ->where('submitted_at', '>=', $since)
            ->selectRaw('DATE(submitted_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        // 7-day learning activity percentage from material completions & submissions
        $days = collect(range(6, 0))->map(function (int $offset) use ($completionsByDay, $subsByDay) {
            $day = Carbon::now()->subDays($offset);
            $key = $day->toDateString();
            $completions = (int) ($completionsByDay[$key] ?? 0);
            $subs = (int) ($subsByDay[$key] ?? 0);

            return [
                'label' => $day->format('D'),
                'value' => min(100, ($completions * 20) + ($subs * 30)),
            ];
        })->values();

        $announcements = Announcement::query()
            ->with(['author:id,name', 'course:id,title'])
            ->where(function ($q) use ($courseIds) {
                $q->whereNull('course_id')->orWhereIn('course_id', $courseIds);
            })
            ->whereNotNull('published_at')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get()
            ->map(fn (Announcement $a) => ApiPayloadMapper::announcement($a))
            ->values();

        $firstEnrollment = $enrollments->first();

        $attemptsUsedByQuiz = QuizAttempt::query()
            ->whereIn('quiz_id', $upcomingExams->pluck('id'))
            ->where('student_id', $student->id)
            ->selectRaw('quiz_id, COUNT(*) as used')
            ->groupBy('quiz_id')
            ->pluck('used', 'quiz_id');

        return response()->json([
            'summary' => [
                'enrolled_courses' => $enrollments->count(),
                'completed_courses' => $enrollments->where('status', 'completed')->count(),
                'pending_assignments' => $pendingAssignments->count(),
                'average_score' => round($avgScore, 1),
            ],
            'continue_learning' => $firstEnrollment?->course
                ? ApiPayloadMapper::course(
                    $firstEnrollment->course,
                    (float) $firstEnrollment->progress_percentage,
                    $firstEnrollment->status
                )
                : null,
            'upcoming_assignments' => $pendingAssignments->take(5)->map(
                fn (Assignment $a) => ApiPayloadMapper::assignment($a, $submissionsByAssignment->get($a->id))
            )->values(),
            'upcoming_exams' => $upcomingExams->map(fn (Quiz $q) => ApiPayloadMapper::quiz(
                $q,
                (int) ($attemptsUsedByQuiz[$q->id] ?? 0)
            ))->values(),
            'learning_progress' => $days,
            'announcements' => $announcements,
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $student = $request->user();
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $sort = $request->string('sort')->toString();

        $query = Enrollment::query()
            ->whereHas('course')
            ->with(['course' => fn ($q) => $q->withCount(['enrollments', 'materials'])->with(['category:id,name', 'subject:id,name', 'instructor:id,name'])])
            ->where('student_id', $student->id)
            ->where('status', '!=', 'dropped');

        if ($status !== '' && $status !== 'Semua') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->whereHas('course', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($sort === 'progress') {
            $query->orderByDesc('progress_percentage');
        } else {
            $query->latest('updated_at');
        }

        $courses = $query->get()
            ->filter(fn (Enrollment $e) => $e->course !== null)
            ->map(fn (Enrollment $e) => ApiPayloadMapper::course(
                $e->course,
                (float) $e->progress_percentage,
                $e->status
            ))
            ->values();

        return response()->json(['data' => $courses]);
    }

    public function joinCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $enrollment = $this->enrollmentService->joinByCode($request->user(), $validated['code']);
        $enrollment->load(['course.category:id,name', 'course.subject:id,name', 'course.instructor:id,name']);

        return response()->json([
            'data' => ApiPayloadMapper::course(
                $enrollment->course,
                (float) $enrollment->progress_percentage,
                $enrollment->status
            ),
        ], 201);
    }

    public function courseDetail(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('view', $course);
        $student = $request->user();

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        $completedIds = MaterialProgress::query()
            ->where('student_id', $student->id)
            ->where('is_completed', true)
            ->whereHas('material', fn ($q) => $q->where('course_id', $course->id))
            ->pluck('material_id')
            ->all();

        $lessons = $course->lessons()
            ->where('is_published', true)
            ->with('materials')
            ->get()
            ->map(fn ($l) => ApiPayloadMapper::lesson($l, $completedIds))
            ->values();

        $assignments = $course->assignments()
            ->where('is_published', true)
            ->get();

        $submissions = Submission::query()
            ->where('student_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        $publishedQuizzes = $course->quizzes()
            ->where('is_published', true)
            ->withCount('questions')
            ->get();

        $usedByQuiz = QuizAttempt::query()
            ->whereIn('quiz_id', $publishedQuizzes->pluck('id'))
            ->where('student_id', $student->id)
            ->selectRaw('quiz_id, COUNT(*) as used')
            ->groupBy('quiz_id')
            ->pluck('used', 'quiz_id');

        $quizzes = $publishedQuizzes->map(fn (Quiz $q) => ApiPayloadMapper::quiz(
            $q,
            (int) ($usedByQuiz[$q->id] ?? 0)
        ))->values();

        $discussions = $course->discussions()
            ->withCount('allComments')
            ->get()
            ->map(fn ($d) => ApiPayloadMapper::discussion($d))
            ->values();

        return response()->json([
            'course' => ApiPayloadMapper::course(
                $course,
                (float) ($enrollment?->progress_percentage ?? 0),
                $enrollment?->status ?? 'active'
            ),
            'lessons' => $lessons,
            'assignments' => $assignments->map(
                fn (Assignment $a) => ApiPayloadMapper::assignment($a, $submissions->get($a->id))
            )->values(),
            'quizzes' => $quizzes,
            'discussions' => $discussions,
        ]);
    }

    public function completeMaterial(Request $request, Course $course, Material $material): JsonResponse
    {
        abort_unless((int) $material->course_id === (int) $course->id, 404);
        Gate::authorize('view', $course);

        $enrollment = $this->enrollmentService->markMaterialCompleted($material, $request->user());

        return response()->json([
            'progress_percentage' => (float) $enrollment->progress_percentage,
        ]);
    }

    public function assignments(Request $request): JsonResponse
    {
        $student = $request->user();
        $search = $request->string('search')->toString();
        $statusFilter = $request->string('status')->toString();

        $courseIds = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', '!=', 'dropped')
            ->pluck('course_id');

        $assignments = Assignment::query()
            ->with('course:id,title')
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderBy('due_at')
            ->get();

        $submissions = Submission::query()
            ->where('student_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        $mapped = $assignments->map(
            fn (Assignment $a) => ApiPayloadMapper::assignment($a, $submissions->get($a->id))
        );

        if ($statusFilter !== '') {
            $mapped = $mapped->where('status', $statusFilter);
        }

        return response()->json(['data' => $mapped->values()]);
    }

    public function assignmentDetail(Request $request, Course $course, Assignment $assignment): JsonResponse
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        Gate::authorize('view', $assignment);

        $sub = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $request->user()->id)
            ->first();

        return response()->json(['data' => ApiPayloadMapper::assignment($assignment, $sub)]);
    }

    public function submitAssignment(Request $request, Course $course, Assignment $assignment): JsonResponse
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        Gate::authorize('submit', $assignment);

        $student = $request->user();

        if ($assignment->due_at && now()->gt($assignment->due_at) && ! $assignment->allow_late_submission) {
            return response()->json(['message' => 'Batas pengumpulan tugas telah berakhir.'], 422);
        }

        $validated = $request->validate([
            'text_answer' => ['nullable', 'string', 'max:30000'],
            'file' => ['nullable', 'file', 'max:25600', 'mimes:pdf,doc,docx,ppt,pptx,zip,png,jpg,jpeg'],
        ]);

        if ($assignment->submission_type === 'file' && ! $request->hasFile('file')) {
            return response()->json(['message' => 'Tugas ini mewajibkan pengumpulan berkas.'], 422);
        }
        if ($assignment->submission_type === 'text' && empty(trim((string) ($validated['text_answer'] ?? '')))) {
            return response()->json(['message' => 'Tugas ini mewajibkan jawaban teks.'], 422);
        }
        if ($assignment->submission_type === 'both' && empty(trim((string) ($validated['text_answer'] ?? ''))) && ! $request->hasFile('file')) {
            return response()->json([
                'message' => 'Isi jawaban teks atau unggah berkas lampiran.',
            ], 422);
        }

        $existing = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && $existing->status === 'graded') {
            return response()->json(['message' => 'Tugas ini sudah dinilai dan tidak dapat diubah.'], 422);
        }

        $fileData = [];
        if ($request->hasFile('file')) {
            if ($existing?->file_path) {
                $this->fileStorage->deleteIfExists($existing->file_path);
            }
            $stored = $this->fileStorage->storePrivate($request->file('file'), "submissions/{$assignment->id}");
            $fileData = [
                'file_path' => $stored['path'],
                'file_name' => $stored['name'],
                'mime_type' => $stored['mime'],
                'file_size' => $stored['size'],
            ];
        }

        $isLate = $assignment->due_at && now()->gt($assignment->due_at);
        $submission = Submission::query()->updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            array_merge([
                'text_answer' => $validated['text_answer'] ?? $existing?->text_answer,
                'status' => $isLate ? 'late' : 'submitted',
                'submitted_at' => now(),
            ], $fileData)
        );

        $this->activityLogService->log('assignment.submitted', "{$student->name} mengumpulkan {$assignment->title}.", $assignment, [], $student);

        return response()->json(['data' => ApiPayloadMapper::submission($submission)]);
    }

    public function quizDetail(Request $request, Course $course, Quiz $quiz): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('view', $quiz);

        $quiz->loadCount('questions');
        $used = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $request->user()->id)
            ->count();

        return response()->json(['data' => ApiPayloadMapper::quiz($quiz, $used)]);
    }

    public function startQuiz(Request $request, Course $course, Quiz $quiz): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        Gate::authorize('attempt', $quiz);

        $attempt = $this->quizAttemptService->startAttempt($quiz, $request->user());

        return response()->json(['data' => ApiPayloadMapper::quizAttempt($attempt, false)]);
    }

    public function autosaveQuiz(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id && (int) $attempt->quiz_id === (int) $quiz->id, 404);
        abort_unless((int) $attempt->student_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_option_id' => ['nullable', 'integer', 'exists:question_options,id'],
            'essay_answer' => ['nullable', 'string', 'max:20000'],
        ]);

        $question = Question::query()->findOrFail($validated['question_id']);
        $answer = $this->quizAttemptService->saveAnswer(
            $attempt,
            $question,
            $validated['selected_option_id'] ?? null,
            $validated['essay_answer'] ?? null
        );

        return response()->json([
            'saved_at' => $answer->answered_at?->toIso8601String() ?? now()->toIso8601String(),
        ]);
    }

    public function submitQuiz(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id && (int) $attempt->quiz_id === (int) $quiz->id, 404);
        abort_unless((int) $attempt->student_id === (int) $request->user()->id, 403);

        $submitted = $this->quizAttemptService->submitAttempt($attempt, false);
        $showResults = (bool) $quiz->show_results;
        $showSolutions = $showResults && (bool) $quiz->show_explanations;

        return response()->json([
            'data' => ApiPayloadMapper::quizAttempt($submitted, $showSolutions, $showResults),
        ]);
    }

    public function quizResult(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        abort_unless((int) $quiz->course_id === (int) $course->id && (int) $attempt->quiz_id === (int) $quiz->id, 404);
        abort_unless((int) $attempt->student_id === (int) $request->user()->id, 403);

        if ($attempt->status === 'in_progress') {
            if (now()->gt($attempt->expires_at)) {
                $attempt = $this->quizAttemptService->submitAttempt($attempt, true);
            } else {
                return response()->json([
                    'message' => 'Ujian masih berlangsung. Kirim jawaban terlebih dahulu sebelum melihat hasil.',
                ], 403);
            }
        }

        $showResults = (bool) $quiz->show_results;
        $showSolutions = $showResults && (bool) $quiz->show_explanations;

        return response()->json([
            'data' => ApiPayloadMapper::quizAttempt($attempt, $showSolutions, $showResults),
        ]);
    }

    public function grades(Request $request): JsonResponse
    {
        $items = Grade::query()
            ->whereHas('course')
            ->where('student_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Grade $g) => ApiPayloadMapper::grade($g))
            ->values();

        return response()->json(['data' => $items]);
    }

    public function gamification(Request $request): JsonResponse
    {
        $student = $request->user();
        $completedLessons = MaterialProgress::query()
            ->where('student_id', $student->id)
            ->where('is_completed', true)
            ->count();
        $submittedAssignments = Submission::query()
            ->where('student_id', $student->id)
            ->count();
        $completedCourses = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'completed')
            ->count();
        $hasPerfectScore = Submission::query()
            ->where('student_id', $student->id)
            ->where('score', '>=', 100)
            ->exists();

        $points = ($completedLessons * 25) + ($submittedAssignments * 50) + ($completedCourses * 200);

        $recentDates = MaterialProgress::query()
            ->where('student_id', $student->id)
            ->where('completed_at', '>=', now()->subDays(30))
            ->pluck('completed_at')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count();

        return response()->json([
            'data' => [
                'points' => $points,
                'streak_days' => $recentDates,
                'completed_courses' => $completedCourses,
                'badges' => [
                    [
                        'code' => 'first_lesson',
                        'title' => 'First Lesson',
                        'description' => 'Menyelesaikan materi pembelajaran pertama.',
                        'unlocked' => $completedLessons >= 1,
                    ],
                    [
                        'code' => 'fast_learner',
                        'title' => 'Fast Learner',
                        'description' => 'Menyelesaikan minimal 3 materi.',
                        'unlocked' => $completedLessons >= 3,
                    ],
                    [
                        'code' => 'perfect_score',
                        'title' => 'Perfect Score',
                        'description' => 'Meraih nilai 100 pada tugas.',
                        'unlocked' => $hasPerfectScore,
                    ],
                    [
                        'code' => 'assignment_master',
                        'title' => 'Assignment Master',
                        'description' => 'Mengumpulkan minimal 5 tugas.',
                        'unlocked' => $submittedAssignments >= 5,
                    ],
                    [
                        'code' => 'learning_champion',
                        'title' => 'Learning Champion',
                        'description' => 'Menyelesaikan minimal 1 kelas penuh.',
                        'unlocked' => $completedCourses >= 1,
                    ],
                ],
            ],
        ]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $currentId = $request->user()->id;

        $studentUsers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_STUDENT))
            ->where('is_active', true)
            ->get(['id', 'name']);

        $studentIds = $studentUsers->pluck('id');

        $matsByStudent = MaterialProgress::query()
            ->whereIn('student_id', $studentIds)
            ->where('is_completed', true)
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $subsByStudent = Submission::query()
            ->whereIn('student_id', $studentIds)
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $doneByStudent = Enrollment::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('course')
            ->where('status', 'completed')
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $students = $studentUsers
            ->map(function (User $u) use ($currentId, $matsByStudent, $subsByStudent, $doneByStudent) {
                $mats = (int) ($matsByStudent[$u->id] ?? 0);
                $subs = (int) ($subsByStudent[$u->id] ?? 0);
                $done = (int) ($doneByStudent[$u->id] ?? 0);

                return [
                    'student_name' => $u->name,
                    'points' => ($mats * 25) + ($subs * 50) + ($done * 200),
                    'is_current_user' => $u->id === $currentId,
                ];
            })
            ->sortByDesc('points')
            ->values()
            ->take(20)
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;

                return $row;
            });

        return response()->json(['data' => $students]);
    }
}
