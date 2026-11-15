<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'instructors_count' => User::whereHas('roles', fn ($q) => $q->where('name', 'instructor'))->count(),
            'students_count' => User::whereHas('roles', fn ($q) => $q->where('name', 'student'))->count(),
            'courses_count' => Course::count(),
            'published_courses_count' => Course::where('status', 'published')->count(),
            'materials_count' => Material::count(),
            'assignments_count' => Assignment::count(),
            'quizzes_count' => Quiz::count(),
            'enrollments_count' => Enrollment::count(),
        ];

        $recentActivities = ActivityLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user?->name ?? 'Sistem',
                'created_at' => $log->created_at?->format('d M Y H:i'),
                'created_at_human' => $log->created_at?->diffForHumans(),
            ]);

        // Real chart data: enrollments by course (top 6) & user distribution by role
        $courseEnrollments = Course::query()
            ->withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->limit(6)
            ->get(['id', 'title', 'code'])
            ->map(fn (Course $c) => [
                'label' => $c->code,
                'title' => $c->title,
                'count' => $c->enrollments_count,
            ]);

        $activityByDay = ActivityLog::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('total', 'day');

        $dailyLabels = [];
        $dailyTotals = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = now()->subDays($i)->format('d M');
            $dailyTotals[] = (int) ($activityByDay[$date] ?? 0);
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'charts' => [
                'courseEnrollments' => $courseEnrollments,
                'dailyActivity' => [
                    'labels' => $dailyLabels,
                    'totals' => $dailyTotals,
                ],
                'roleBreakdown' => [
                    ['label' => 'Administrator', 'count' => User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->count()],
                    ['label' => 'Pengajar', 'count' => $stats['instructors_count']],
                    ['label' => 'Siswa', 'count' => $stats['students_count']],
                ],
            ],
        ]);
    }
}
