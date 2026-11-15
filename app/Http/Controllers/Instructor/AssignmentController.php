<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\GradeSubmissionRequest;
use App\Http\Requests\Instructor\StoreAssignmentRequest;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Notifications\GradePublishedNotification;
use App\Notifications\NewAssignmentNotification;
use App\Services\ActivityLogService;
use App\Services\EnrollmentService;
use App\Services\FileStorageService;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorageService,
        private readonly GradingService $gradingService,
        private readonly EnrollmentService $enrollmentService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function store(StoreAssignmentRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $data = $request->validated();
        $data['course_id'] = $course->id;
        $data['created_by'] = $request->user()->id;
        $data['allow_late_submission'] = $request->boolean('allow_late_submission');
        $data['is_published'] = $request->boolean('is_published', true);

        if ($request->hasFile('attachment')) {
            $stored = $this->fileStorageService->storePrivate($request->file('attachment'), "assignments/{$course->id}");
            $data['attachment_path'] = $stored['path'];
            $data['attachment_name'] = $stored['name'];
        }

        $assignment = Assignment::query()->create($data);

        if ($assignment->is_published) {
            Notification::send($course->students, new NewAssignmentNotification($assignment));
        }

        $this->activityLogService->log('instructor.assignment_created', "Membuat tugas {$assignment->title}.", $assignment);

        return back()->with('success', 'Tugas berhasil dibuat.');
    }

    public function show(Course $course, Assignment $assignment): Response
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $assignment);

        $submissions = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->with('student:id,name,email,identity_number')
            ->latest('submitted_at')
            ->paginate(15);

        return Inertia::render('Instructor/Assignments/Show', [
            'course' => $course->only(['id', 'title', 'code']),
            'assignment' => $assignment,
            'submissions' => $submissions,
        ]);
    }

    public function update(StoreAssignmentRequest $request, Course $course, Assignment $assignment): RedirectResponse
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $assignment);

        $data = $request->validated();
        $data['allow_late_submission'] = $request->boolean('allow_late_submission');
        $data['is_published'] = $request->boolean('is_published', true);

        if ($request->hasFile('attachment')) {
            $this->fileStorageService->deleteIfExists($assignment->attachment_path, 'local');
            $stored = $this->fileStorageService->storePrivate($request->file('attachment'), "assignments/{$course->id}");
            $data['attachment_path'] = $stored['path'];
            $data['attachment_name'] = $stored['name'];
        }

        $assignment->update($data);

        return back()->with('success', 'Tugas berhasil diperbarui.');
    }

    public function gradeSubmission(GradeSubmissionRequest $request, Submission $submission): RedirectResponse
    {
        $submission->loadMissing(['assignment.course', 'student']);
        Gate::authorize('manage', $submission->assignment);

        $submission->update([
            'score' => $request->float('score'),
            'feedback' => $request->input('feedback'),
            'status' => 'graded',
            'graded_at' => now(),
            'graded_by' => $request->user()->id,
        ]);

        $course = $submission->assignment->course;
        $this->gradingService->recalculateStudentGrade($course, $submission->student, $request->user());
        $this->enrollmentService->issueCertificateIfEligible($course, $submission->student);

        $submission->student->notify(new GradePublishedNotification(
            $course,
            $submission->assignment->title,
            (float) $submission->score,
            route('student.courses.show', $course->id)
        ));

        $this->activityLogService->log(
            'instructor.submission_graded',
            "Menilai tugas {$submission->assignment->title} untuk {$submission->student->name} ({$submission->score}).",
            $submission
        );

        return back()->with('success', "Nilai untuk {$submission->student->name} berhasil disimpan.");
    }

    public function destroy(Course $course, Assignment $assignment): RedirectResponse
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        Gate::authorize('manage', $assignment);

        $this->fileStorageService->deleteIfExists($assignment->attachment_path, 'local');
        $assignment->delete();

        return back()->with('success', 'Tugas berhasil dihapus.');
    }
}
