<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportExportService $reportExportService) {}

    public function index(Request $request): Response
    {
        $courseId = $request->integer('course_id') ?: null;
        $periodId = $request->integer('period_id') ?: null;

        $rows = $this->reportExportService->getLearningReportRows($courseId, $periodId);

        return Inertia::render('Admin/Reports/Index', [
            'rows' => $rows,
            'courses' => Course::orderBy('title')->get(['id', 'title', 'code']),
            'periods' => AcademicPeriod::orderByDesc('start_date')->get(['id', 'name']),
            'filters' => [
                'course_id' => $courseId,
                'period_id' => $periodId,
            ],
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
