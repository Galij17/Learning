<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Grade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $student = $request->user();

        $enrollments = Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['active', 'completed'])
            ->with([
                'course:id,title,code,instructor_id,category_id,status',
                'course.instructor:id,name',
                'course.category:id,name',
            ])
            ->latest('updated_at')
            ->get();

        $courseIds = $enrollments->pluck('course_id');

        $upcomingAssignments = Assignment::query()
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('due_at')->orWhere('due_at', '>=', now()))
            ->with([
                'course:id,title,code',
                'submissions' => fn ($q) => $q->where('student_id', $student->id),
            ])
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        $recentAnnouncements = Announcement::query()
            ->where(fn ($q) => $q->whereNull('course_id')->orWhereIn('course_id', $courseIds))
            ->with(['course:id,title,code', 'author:id,name'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->with('course:id,title,code')
            ->get();

        $stats = [
            'enrolled_courses' => $enrollments->count(),
            'completed_courses' => $enrollments->where('status', 'completed')->count(),
            'average_progress' => round((float) $enrollments->avg('progress_percentage'), 1),
            'certificates_count' => Certificate::where('student_id', $student->id)->count(),
        ];

        $progressChart = $enrollments->take(6)->map(fn (Enrollment $e) => [
            'label' => $e->course?->code ?? 'KLS',
            'progress' => (float) $e->progress_percentage,
            'score' => (float) ($e->final_score ?? 0),
        ]);

        return Inertia::render('Student/Dashboard', [
            'stats' => $stats,
            'enrollments' => $enrollments,
            'upcomingAssignments' => $upcomingAssignments,
            'recentAnnouncements' => $recentAnnouncements,
            'grades' => $grades,
            'progressChart' => $progressChart,
        ]);
    }
}
