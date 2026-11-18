<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\QuizResource;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\MaterialProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
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
                'percentage' => min(100, ($completions * 25) + ($subs * 40)),
            ];
        })->values();

        $announcements = Announcement::query()
            ->where(function ($q) use ($courseIds) {
                $q->whereNull('course_id')->orWhereIn('course_id', $courseIds);
            })
            ->whereNotNull('published_at')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get()
            ->map(fn (Announcement $a) => (new AnnouncementResource($a))->resolve())
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
                ? (new CourseResource(
                    $firstEnrollment->course,
                    (float) $firstEnrollment->progress_percentage,
                    $firstEnrollment->status
                ))->resolve()
                : null,
            'upcoming_assignments' => $pendingAssignments->take(5)->map(
                fn (Assignment $a) => (new AssignmentResource($a, $submissionsByAssignment->get($a->id)))->resolve()
            )->values(),
            'upcoming_exams' => $upcomingExams->map(fn (Quiz $q) => (new QuizResource(
                $q,
                (int) ($attemptsUsedByQuiz[$q->id] ?? 0)
            ))->resolve())->values(),
            'learning_progress' => $days,
            'announcements' => $announcements,
        ]);
    }
}
