<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Gather learning report rows filtered optionally by course or academic period.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLearningReportRows(?int $courseId = null, ?int $periodId = null): array
    {
        $query = Enrollment::query()
            ->with([
                'student:id,name,email,identity_number',
                'course:id,title,code,instructor_id,academic_period_id,category_id',
                'course.instructor:id,name',
                'course.academicPeriod:id,name',
                'course.category:id,name',
            ])
            ->orderByDesc('updated_at');

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        if ($periodId) {
            $query->whereHas('course', fn ($q) => $q->where('academic_period_id', $periodId));
        }

        return $query->get()->map(function (Enrollment $enrollment) {
            $grade = $enrollment->course?->grades()
                ->where('student_id', $enrollment->student_id)
                ->first();

            return [
                'identity_number' => $enrollment->student?->identity_number ?? '-',
                'student_name' => $enrollment->student?->name ?? '-',
                'student_email' => $enrollment->student?->email ?? '-',
                'course_code' => $enrollment->course?->code ?? '-',
                'course_title' => $enrollment->course?->title ?? '-',
                'category' => $enrollment->course?->category?->name ?? '-',
                'period' => $enrollment->course?->academicPeriod?->name ?? '-',
                'instructor' => $enrollment->course?->instructor?->name ?? '-',
                'status' => strtoupper($enrollment->status),
                'progress' => (float) $enrollment->progress_percentage,
                'assignment_avg' => (float) ($grade?->assignment_avg ?? 0),
                'quiz_avg' => (float) ($grade?->quiz_avg ?? 0),
                'exam_avg' => (float) ($grade?->exam_avg ?? 0),
                'final_score' => (float) ($grade?->final_score ?? $enrollment->final_score ?? 0),
                'letter_grade' => $grade?->letter_grade ?? '-',
            ];
        })->all();
    }

    public function exportPdf(?int $courseId = null, ?int $periodId = null): Response
    {
        $rows = $this->getLearningReportRows($courseId, $periodId);
        $course = $courseId ? Course::with('instructor')->find($courseId) : null;

        $pdf = Pdf::loadView('pdf.learning-report', [
            'rows' => $rows,
            'course' => $course,
            'siteName' => Setting::getValue('site_name', 'VerdantLMS'),
            'institutionName' => Setting::getValue('institution_name', 'Universitas Tawangmangu'),
            'generatedAt' => now()->format('d M Y H:i'),
        ])->setPaper('a4', 'landscape');

        $filename = 'laporan-pembelajaran-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(?int $courseId = null, ?int $periodId = null): StreamedResponse
    {
        $rows = $this->getLearningReportRows($courseId, $periodId);
        $filename = 'laporan-pembelajaran-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($rows) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'NIM / NIS',
                'Nama Peserta',
                'Email',
                'Kode Kelas',
                'Nama Kelas',
                'Kategori',
                'Periode',
                'Pengajar',
                'Status',
                'Progres (%)',
                'Rata Tugas',
                'Rata Kuis',
                'Rata Ujian',
                'Nilai Akhir',
                'Huruf Mutu',
            ]));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues([
                    $row['identity_number'],
                    $row['student_name'],
                    $row['student_email'],
                    $row['course_code'],
                    $row['course_title'],
                    $row['category'],
                    $row['period'],
                    $row['instructor'],
                    $row['status'],
                    $row['progress'],
                    $row['assignment_avg'],
                    $row['quiz_avg'],
                    $row['exam_avg'],
                    $row['final_score'],
                    $row['letter_grade'],
                ]));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadCertificatePdf(Certificate $certificate): Response
    {
        $certificate->loadMissing(['student', 'course.instructor', 'course.academicPeriod']);

        $pdf = Pdf::loadView('pdf.certificate', [
            'certificate' => $certificate,
            'siteName' => Setting::getValue('site_name', 'VerdantLMS'),
            'institutionName' => Setting::getValue('institution_name', 'Universitas Tawangmangu'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("sertifikat-{$certificate->certificate_number}.pdf");
    }
}
