<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialAccessController extends Controller
{
    public function __construct(private readonly FileStorageService $fileStorageService) {}

    public function download(Request $request, Material $material): StreamedResponse
    {
        $material->loadMissing('course');
        Gate::authorize('view', $material->course);

        if (! $material->is_published && $request->user()->isStudent()) {
            abort(403, 'Materi ini belum dipublikasikan.');
        }

        abort_unless($material->file_path, 404, 'Materi ini tidak memiliki berkas.');

        return $this->fileStorageService->downloadPrivate(
            $material->file_path,
            $material->file_name ?? basename($material->file_path),
            $material->storage_disk ?? 'local'
        );
    }

    public function stream(Request $request, Material $material): StreamedResponse
    {
        $material->loadMissing('course');
        Gate::authorize('view', $material->course);

        if (! $material->is_published && $request->user()->isStudent()) {
            abort(403, 'Materi ini belum dipublikasikan.');
        }

        abort_unless($material->file_path, 404, 'Materi ini tidak memiliki berkas.');

        return $this->fileStorageService->streamPrivate(
            $material->file_path,
            $material->mime_type ?? 'application/octet-stream',
            $material->storage_disk ?? 'local'
        );
    }
}
