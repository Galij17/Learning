<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorageService
{
    private const INLINE_SAFE_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'video/mp4',
        'video/webm',
    ];

    /**
     * Store a private uploaded file (materials, assignments, submissions).
     *
     * @return array{path: string, name: string, mime: string, size: int, disk: string}
     */
    public function storePrivate(UploadedFile $file, string $directory): array
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $safeFilename = Str::uuid()->toString() . ($extension !== '' ? ".{$extension}" : '');
        $path = $file->storeAs($directory, $safeFilename, 'local');

        if ($path === false) {
            throw new \RuntimeException('Gagal menyimpan berkas privat.');
        }

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
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $safeFilename = Str::uuid()->toString() . ($extension !== '' ? ".{$extension}" : '');
        $path = $file->storeAs($directory, $safeFilename, 'public');

        if ($path === false) {
            throw new \RuntimeException('Gagal menyimpan berkas publik.');
        }

        return $path;
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

        $normalizedMime = strtolower(trim(explode(';', $mimeType)[0]));
        $isSafeInline = in_array($normalizedMime, self::INLINE_SAFE_MIMES, true);

        return Storage::disk($disk)->response($path, null, [
            'Content-Type' => $isSafeInline ? $normalizedMime : 'application/octet-stream',
            'Content-Disposition' => $isSafeInline ? 'inline' : 'attachment',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
