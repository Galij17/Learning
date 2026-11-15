<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitAssignmentRequest;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Services\ActivityLogService;
use App\Services\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorageService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function show(Request $request, Course $course, Assignment $assignment): Response
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        Gate::authorize('view', $assignment);

        $submission = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $request->user()->id)
            ->first();

        return Inertia::render('Student/Assignments/Show', [
            'course' => $course->only(['id', 'title', 'code']),
            'assignment' => $assignment->load('lesson:id,title,meeting_number'),
            'submission' => $submission,
            'isPastDue' => $assignment->due_at ? now()->gt($assignment->due_at) : false,
        ]);
    }

    public function submit(SubmitAssignmentRequest $request, Course $course, Assignment $assignment): RedirectResponse
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        Gate::authorize('submit', $assignment);

        $isLate = $assignment->due_at ? now()->gt($assignment->due_at) : false;
        if ($isLate && ! $assignment->allow_late_submission) {
            return back()->with('error', 'Batas waktu pengumpulan tugas telah berakhir dan pengumpulan terlambat tidak diizinkan.');
        }

        $existing = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $request->user()->id)
            ->first();

        if ($existing && $existing->status === 'graded') {
            return back()->with('error', 'Tugas yang sudah dinilai tidak dapat diubah.');
        }

        $payload = [
            'text_answer' => $request->input('text_answer'),
            'status' => $isLate ? 'late' : 'submitted',
            'submitted_at' => now(),
        ];

        if ($request->hasFile('file')) {
            if ($existing?->file_path) {
                $this->fileStorageService->deleteIfExists($existing->file_path, 'local');
            }
            $stored = $this->fileStorageService->storePrivate($request->file('file'), "submissions/{$assignment->id}");
            $payload['file_path'] = $stored['path'];
            $payload['file_name'] = $stored['name'];
            $payload['mime_type'] = $stored['mime'];
            $payload['file_size'] = $stored['size'];
        }

        $submission = Submission::query()->updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $request->user()->id],
            $payload
        );

        $this->activityLogService->log('student.assignment_submitted', "Mengumpulkan tugas {$assignment->title}.", $submission);

        return back()->with('success', 'Tugas berhasil dikumpulkan.');
    }
}
