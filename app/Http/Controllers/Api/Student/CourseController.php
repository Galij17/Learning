<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\DiscussionResource;
use App\Http\Resources\LessonResource;
use App\Http\Resources\QuizResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Submission;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollmentService,
    ) {}

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
            ->map(fn (Enrollment $e) => (new CourseResource(
                $e->course,
                (float) $e->progress_percentage,
                $e->status
            ))->resolve())
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
            'data' => (new CourseResource(
                $enrollment->course,
                (float) $enrollment->progress_percentage,
                $enrollment->status
            ))->resolve(),
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
            ->pluck('material_id')
            ->all();

        $lessons = $course->lessons()
            ->where('is_published', true)
            ->with('materials')
            ->get()
            ->map(fn ($l) => (new LessonResource($l, $completedIds))->resolve())
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

        $quizzes = $publishedQuizzes->map(fn (Quiz $q) => (new QuizResource(
            $q,
            (int) ($usedByQuiz[$q->id] ?? 0)
        ))->resolve())->values();

        $discussions = $course->discussions()
            ->withCount('allComments')
            ->get()
            ->map(fn ($d) => (new DiscussionResource($d))->resolve())
            ->values();

        return response()->json([
            'course' => (new CourseResource(
                $course,
                (float) ($enrollment?->progress_percentage ?? 0),
                $enrollment?->status ?? 'active'
            ))->resolve(),
            'lessons' => $lessons,
            'assignments' => $assignments->map(
                fn (Assignment $a) => (new AssignmentResource($a, $submissions->get($a->id)))->resolve()
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
}
