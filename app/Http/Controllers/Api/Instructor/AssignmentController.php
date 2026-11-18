<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Notifications\GradePublishedNotification;
use App\Services\ActivityLogService;
use App\Services\EnrollmentService;
use App\Services\FileStorageService;
use App\Services\GradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
        private readonly GradingService $gradingService,
        private readonly EnrollmentService $enrollmentService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function storeAssignment(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:20000'],
            'due_at' => ['nullable', 'date'],
            'max_score' => ['nullable', 'numeric', 'min:1', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png'],
        ]);

        $attachmentMeta = [];
        if ($request->hasFile('attachment')) {
            $stored = $this->fileStorage->storePrivate($request->file('attachment'), "assignments/{$course->id}");
            $attachmentMeta = [
                'attachment_path' => $stored['path'],
                'attachment_name' => $stored['name'],
            ];
        }

        $assignment = Assignment::query()->create(array_merge([
            'course_id' => $course->id,
            'created_by' => $request->user()->id,
            'title' => trim($validated['title']),
            'description' => trim($validated['description']),
            'due_at' => $validated['due_at'] ?? now()->addDays(7),
            'max_score' => $validated['max_score'] ?? 100,
            'is_published' => true,
        ], $attachmentMeta));

        return response()->json(['data' => (new AssignmentResource($assignment))->resolve()], 201);
    }

    public function submissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $courseIds = Course::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('instructor_id', $user->id))
            ->pluck('id');
        $assignmentIds = Assignment::query()->whereIn('course_id', $courseIds)->pluck('id');

        $subs = Submission::query()
            ->with('student:id,name')
            ->whereIn('assignment_id', $assignmentIds)
            ->latest('submitted_at')
            ->get()
            ->map(fn (Submission $s) => (new SubmissionResource($s))->resolve())
            ->values();

        return response()->json(['data' => $subs]);
    }

    public function gradeSubmission(Request $request, Submission $submission): JsonResponse
    {
        $submission->loadMissing(['assignment.course', 'student']);
        Gate::authorize('manage', $submission->assignment);

        $maxScore = (float) ($submission->assignment?->max_score ?? 100);
        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', "max:{$maxScore}"],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update([
            'score' => $validated['score'],
            'feedback' => $validated['feedback'] ?? null,
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

        return response()->json(['data' => (new SubmissionResource($submission->fresh()))->resolve()]);
    }
}
