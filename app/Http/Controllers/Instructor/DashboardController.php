<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $instructor = $request->user();

        $courses = Course::query()
            ->where('instructor_id', $instructor->id)
            ->with(['category:id,name', 'academicPeriod:id,name'])
            ->withCount(['enrollments', 'lessons', 'assignments', 'quizzes'])
            ->latest()
            ->get();

        $courseIds = $courses->pluck('id');

        $pendingSubmissions = Submission::query()
            ->whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->whereNull('score')
            ->with(['student:id,name,identity_number', 'assignment:id,title,course_id', 'assignment.course:id,title,code'])
            ->latest('submitted_at')
            ->limit(8)
            ->get();

        $pendingEssayAttempts = QuizAttempt::query()
            ->whereHas('quiz', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->where('needs_manual_grading', true)
            ->with(['student:id,name', 'quiz:id,title,course_id', 'quiz.course:id,title,code'])
            ->latest('submitted_at')
            ->limit(6)
            ->get();

        $stats = [
            'courses_count' => $courses->count(),
            'total_students' => $courses->sum('enrollments_count'),
            'pending_submissions_count' => Submission::whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))->whereNull('score')->count(),
            'pending_essays_count' => QuizAttempt::whereHas('quiz', fn ($q) => $q->whereIn('course_id', $courseIds))->where('needs_manual_grading', true)->count(),
        ];

        $chartCourses = $courses->take(6)->map(fn (Course $c) => [
            'label' => $c->code,
            'students' => $c->enrollments_count,
            'avg_progress' => round((float) $c->enrollments()->avg('progress_percentage'), 1),
        ]);

        return Inertia::render('Instructor/Dashboard', [
            'stats' => $stats,
            'courses' => $courses,
            'pendingSubmissions' => $pendingSubmissions,
            'pendingEssayAttempts' => $pendingEssayAttempts,
            'chartCourses' => $chartCourses,
        ]);
    }
}
