<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportExportService $reportExportService,
    ) {}

    public function reports(): JsonResponse
    {
        $avgByCourse = Grade::query()
            ->selectRaw('course_id, AVG(final_score) as avg_score')
            ->groupBy('course_id')
            ->pluck('avg_score', 'course_id');

        $courses = Course::query()->withCount('enrollments')->get()->map(function (Course $c) use ($avgByCourse) {
            return [
                'title' => $c->title,
                'enrollments' => (int) $c->enrollments_count,
                'avg_score' => round((float) ($avgByCourse[$c->id] ?? 0.0), 1),
            ];
        })->values();

        return response()->json([
            'active_users_count' => User::query()->where('is_active', true)->count(),
            'published_courses_count' => Course::query()->where('status', 'published')->count(),
            'submissions_count' => Submission::query()->count(),
            'average_platform_score' => round((float) (Grade::query()->avg('final_score') ?? 0.0), 1),
            'course_reports' => $courses,
        ]);
    }

    public function exportPdf(Request $request): HttpResponse
    {
        return $this->reportExportService->exportPdf(
            $request->integer('course_id') ?: null,
            $request->integer('period_id') ?: null
        );
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        return $this->reportExportService->exportExcel(
            $request->integer('course_id') ?: null,
            $request->integer('period_id') ?: null
        );
    }
}
