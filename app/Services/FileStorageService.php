<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorageService
{
    /**
     * Store a private uploaded file (materials, assignments, submissions).
     *
     * @return array{path: string, name: string, mime: string, size: int, disk: string}
     */
    public function storePrivate(UploadedFile $file, string $directory): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $safeFilename = Str::uuid()->toString() . ($extension !== '' ? ".{$extension}" : '');
        $path = $file->storeAs($directory, $safeFilename, 'local');

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => (int) $file->getSize(),
            'disk' => 'local',
        ];
    }

    /**
     * Store a public asset (site logo, course thumbnail, avatar).
     */
    public function storePublic(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $safeFilename = Str::uuid()->toString() . ($extension !== '' ? ".{$extension}" : '');

        return $file->storeAs($directory, $safeFilename, 'public');
    }

    public function deleteIfExists(?string $path, string $disk = 'local'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function downloadPrivate(string $path, string $originalName, string $disk = 'local'): StreamedResponse
    {
        abort_unless(Storage::disk($disk)->exists($path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk($disk)->download($path, $originalName);
    }

    public function streamPrivate(string $path, string $mimeType, string $disk = 'local'): StreamedResponse
    {
        abort_unless(Storage::disk($disk)->exists($path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk($disk)->response($path, null, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
        ]);
    }
}
