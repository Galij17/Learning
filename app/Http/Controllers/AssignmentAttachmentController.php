<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentAttachmentController extends Controller
{
    public function __construct(private readonly FileStorageService $fileStorageService) {}

    public function downloadAssignment(Assignment $assignment): StreamedResponse
    {
        $assignment->loadMissing('course');
        Gate::authorize('view', $assignment);

        abort_unless($assignment->attachment_path, 404, 'Lampiran tugas tidak tersedia.');

        return $this->fileStorageService->downloadPrivate(
            $assignment->attachment_path,
            $assignment->attachment_name ?? basename($assignment->attachment_path),
            'local'
        );
    }

    public function downloadSubmission(Request $request, Submission $submission): StreamedResponse
    {
        $submission->loadMissing('assignment.course');
        $user = $request->user();

        $isOwner = (int) $submission->student_id === (int) $user->id;
        $canManage = Gate::allows('manage', $submission->assignment);

        abort_unless($isOwner || $canManage, 403, 'Anda tidak berwenang mengakses berkas pengumpulan ini.');
        abort_unless($submission->file_path, 404, 'Berkas pengumpulan tidak ditemukan.');

        return $this->fileStorageService->downloadPrivate(
            $submission->file_path,
            $submission->file_name ?? basename($submission->file_path),
            'local'
        );
    }
}
