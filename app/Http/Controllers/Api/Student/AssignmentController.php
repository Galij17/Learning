<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Services\ActivityLogService;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function assignments(Request $request): JsonResponse
    {
        $student = $request->user();
        $search = $request->string('search')->toString();
        $statusFilter = $request->string('status')->toString();

        $courseIds = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', '!=', 'dropped')
            ->pluck('course_id');

        $assignments = Assignment::query()
            ->with('course:id,title')
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderBy('due_at')
            ->get();

        $submissions = Submission::query()
            ->where('student_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        $mapped = $assignments->map(
            fn (Assignment $a) => (new AssignmentResource($a, $submissions->get($a->id)))->resolve()
        );

        if ($statusFilter !== '') {
            $mapped = $mapped->where('status', $statusFilter);
        }

        return response()->json(['data' => $mapped->values()]);
    }

    public function assignmentDetail(Request $request, Course $course, Assignment $assignment): JsonResponse
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        Gate::authorize('view', $assignment);

        $sub = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $request->user()->id)
            ->first();

        return response()->json(['data' => (new AssignmentResource($assignment, $sub))->resolve()]);
    }

    public function submitAssignment(Request $request, Course $course, Assignment $assignment): JsonResponse
    {
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);
        Gate::authorize('submit', $assignment);

        $student = $request->user();

        if ($assignment->due_at && now()->gt($assignment->due_at) && ! $assignment->allow_late_submission) {
            return response()->json(['message' => 'Batas pengumpulan tugas telah berakhir.'], 422);
        }

        $validated = $request->validate([
            'text_answer' => ['nullable', 'string', 'max:30000'],
            'file' => ['nullable', 'file', 'max:25600', 'mimes:pdf,doc,docx,ppt,pptx,zip,png,jpg,jpeg'],
        ]);

        if ($assignment->submission_type === 'file' && ! $request->hasFile('file')) {
            return response()->json(['message' => 'Tugas ini mewajibkan pengumpulan berkas.'], 422);
        }
        if ($assignment->submission_type === 'text' && empty(trim((string) ($validated['text_answer'] ?? '')))) {
            return response()->json(['message' => 'Tugas ini mewajibkan jawaban teks.'], 422);
        }
        if ($assignment->submission_type === 'both' && empty(trim((string) ($validated['text_answer'] ?? ''))) && ! $request->hasFile('file')) {
            return response()->json([
                'message' => 'Isi jawaban teks atau unggah berkas lampiran.',
            ], 422);
        }

        $existing = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && $existing->status === 'graded') {
            return response()->json(['message' => 'Tugas ini sudah dinilai dan tidak dapat diubah.'], 422);
        }

        $fileData = [];
        if ($request->hasFile('file')) {
            if ($existing?->file_path) {
                $this->fileStorage->deleteIfExists($existing->file_path);
            }
            $stored = $this->fileStorage->storePrivate($request->file('file'), "submissions/{$assignment->id}");
            $fileData = [
                'file_path' => $stored['path'],
                'file_name' => $stored['name'],
                'mime_type' => $stored['mime'],
                'file_size' => $stored['size'],
            ];
        }

        $submission = Submission::query()->updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            array_merge([
                'text_answer' => $validated['text_answer'] ?? null,
                'status' => $assignment->due_at && now()->gt($assignment->due_at) ? 'late' : 'submitted',
                'submitted_at' => now(),
            ], $fileData)
        );

        $this->activityLogService->log('assignment.submitted', "{$student->name} mengumpulkan {$assignment->title}.", $assignment, [], $student);

        return response()->json(['data' => (new SubmissionResource($submission))->resolve()]);
    }
}
