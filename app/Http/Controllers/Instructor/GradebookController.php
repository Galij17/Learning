<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Grade;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GradebookController extends Controller
{
    public function __construct(
        private readonly GradingService $gradingService,
        private readonly EnrollmentService $enrollmentService
    ) {}

    public function index(Course $course): Response
    {
        Gate::authorize('update', $course);

        $enrollments = $course->enrollments()
            ->with('student:id,name,email,identity_number')
            ->get();

        $gradesByStudent = Grade::query()
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('student_id');

        $rows = $enrollments->map(function ($enrollment) use ($gradesByStudent) {
            $grade = $gradesByStudent->get($enrollment->student_id);

            return [
                'student_id' => $enrollment->student_id,
                'student_name' => $enrollment->student?->name,
                'identity_number' => $enrollment->student?->identity_number,
                'email' => $enrollment->student?->email,
                'progress_percentage' => (float) $enrollment->progress_percentage,
                'assignment_avg' => (float) ($grade?->assignment_avg ?? 0),
                'quiz_avg' => (float) ($grade?->quiz_avg ?? 0),
                'exam_avg' => (float) ($grade?->exam_avg ?? 0),
                'final_score' => (float) ($grade?->final_score ?? 0),
                'letter_grade' => $grade?->letter_grade ?? '-',
                'instructor_feedback' => $grade?->instructor_feedback ?? '',
            ];
        });

        return Inertia::render('Instructor/Gradebook/Index', [
            'course' => $course,
            'rows' => $rows,
        ]);
    }

    public function updateFeedback(Request $request, Course $course, User $student): RedirectResponse
    {
        Gate::authorize('update', $course);

        $enrolledStudent = $course->students()
            ->where('users.id', $student->id)
            ->wherePivot('status', '!=', 'dropped')
            ->firstOrFail();

        $validated = $request->validate([
            'instructor_feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->gradingService->recalculateStudentGrade(
            $course,
            $enrolledStudent,
            $request->user(),
            $validated['instructor_feedback'] ?? null
        );

        $this->enrollmentService->issueCertificateIfEligible($course, $enrolledStudent);

        return back()->with('success', "Rekap nilai dan catatan untuk {$enrolledStudent->name} berhasil diperbarui.");
    }

    public function recalculateAll(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $this->gradingService->recalculateCourseGrades($course, $request->user());
        $activeStudents = $course->students()
            ->wherePivot('status', '!=', 'dropped')
            ->get();

        foreach ($activeStudents as $student) {
            $this->enrollmentService->issueCertificateIfEligible($course, $student);
        }

        return back()->with('success', 'Seluruh nilai kelas dan kelayakan sertifikat telah dihitung ulang.');
    }
}
