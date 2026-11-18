<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $activityByDay = ActivityLog::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        $userStats = collect(range(6, 0))->map(function (int $offset) use ($activityByDay) {
            $day = Carbon::now()->subDays($offset);

            return [
                'label' => $day->format('D'),
                'count' => (int) ($activityByDay[$day->toDateString()] ?? 0),
            ];
        })->values();

        return response()->json([
            'total_users' => User::query()->count(),
            'total_students' => User::query()->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_STUDENT))->count(),
            'total_teachers' => User::query()->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_INSTRUCTOR))->count(),
            'total_courses' => Course::query()->count(),
            'active_classes' => Course::query()->where('status', 'published')->count(),
            'total_assignments' => Assignment::query()->count(),
            'recent_activities' => ActivityLog::query()->latest()->limit(8)->pluck('description')->values(),
            'user_stats' => $userStats,
        ]);
    }

    public function activityLogs(): JsonResponse
    {
        $items = ActivityLog::query()
            ->with('user:id,name')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (ActivityLog $l) => [
                'id' => $l->id,
                'action' => $l->action,
                'description' => $l->description,
                'actor_name' => $l->user?->name ?? 'System',
                'created_at' => $l->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json(['data' => $items]);
    }
}
