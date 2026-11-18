<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function __construct(private readonly ReportExportService $reportExportService) {}

    public function index(Request $request): Response
    {
        $certificates = Certificate::query()
            ->whereHas('course')
            ->with(['course:id,title,code,instructor_id', 'course.instructor:id,name'])
            ->where('student_id', $request->user()->id)
            ->latest('issued_at')
            ->paginate(12);

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
        ]);
    }

    public function show(Certificate $certificate): Response
    {
        Gate::authorize('view', $certificate);
        $certificate->load(['student:id,name,identity_number', 'course:id,title,code,instructor_id,academic_period_id', 'course.instructor:id,name', 'course.academicPeriod:id,name']);

        return Inertia::render('Certificates/Show', [
            'certificate' => $certificate,
        ]);
    }

    public function download(Certificate $certificate): HttpResponse
    {
        Gate::authorize('view', $certificate);

        return $this->reportExportService->downloadCertificatePdf($certificate);
    }

    public function verify(string $hash): Response
    {
        $certificate = Certificate::query()
            ->with([
                'student' => fn ($q) => $q->withTrashed()->select('id', 'name', 'identity_number'),
                'course' => fn ($q) => $q->withTrashed()->select('id', 'title', 'code', 'instructor_id'),
                'course.instructor' => fn ($q) => $q->withTrashed()->select('id', 'name'),
            ])
            ->where('verification_hash', $hash)
            ->first();

        return Inertia::render('Certificates/Verify', [
            'certificate' => $certificate,
            'hash' => $hash,
        ]);
    }
}
