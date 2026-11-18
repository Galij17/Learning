<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
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
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Quiz $q) => (new QuizResource($q))->resolve())
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
}
