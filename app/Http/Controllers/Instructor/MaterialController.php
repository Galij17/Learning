<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreMaterialRequest;
use App\Models\Course;
use App\Models\Material;
use App\Services\ActivityLogService;
use App\Services\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MaterialController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorageService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function store(StoreMaterialRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $lesson = $course->lessons()->findOrFail($request->integer('lesson_id'));

        $payload = [
            'lesson_id' => $lesson->id,
            'course_id' => $course->id,
            'title' => $request->string('title')->toString(),
            'type' => $request->string('type')->toString(),
            'video_url' => $request->input('video_url'),
            'content' => $request->input('content'),
            'sort_order' => $request->integer('sort_order') ?: ($lesson->materials()->count() + 1),
            'is_published' => $request->boolean('is_published', true),
            'storage_disk' => 'local',
        ];

        if ($request->hasFile('file')) {
            $stored = $this->fileStorageService->storePrivate($request->file('file'), "materials/{$course->id}");
            $payload['file_path'] = $stored['path'];
            $payload['file_name'] = $stored['name'];
            $payload['mime_type'] = $stored['mime'];
            $payload['file_size'] = $stored['size'];
            $payload['storage_disk'] = $stored['disk'];
        }

        $material = Material::query()->create($payload);
        $this->activityLogService->log('instructor.material_uploaded', "Mengunggah materi {$material->title} pada {$course->title}.", $material);

        return back()->with('success', 'Materi pembelajaran berhasil ditambahkan.');
    }

    public function update(StoreMaterialRequest $request, Course $course, Material $material): RedirectResponse
    {
        abort_unless((int) $material->course_id === (int) $course->id, 404);
        Gate::authorize('update', $course);

        $lesson = $course->lessons()->findOrFail($request->integer('lesson_id'));

        $payload = [
            'lesson_id' => $lesson->id,
            'title' => $request->string('title')->toString(),
            'type' => $request->string('type')->toString(),
            'video_url' => $request->input('video_url'),
            'content' => $request->input('content'),
            'sort_order' => $request->integer('sort_order') ?: $material->sort_order,
            'is_published' => $request->boolean('is_published', true),
        ];

        if ($request->hasFile('file')) {
            $this->fileStorageService->deleteIfExists($material->file_path, $material->storage_disk ?? 'local');
            $stored = $this->fileStorageService->storePrivate($request->file('file'), "materials/{$course->id}");
            $payload['file_path'] = $stored['path'];
            $payload['file_name'] = $stored['name'];
            $payload['mime_type'] = $stored['mime'];
            $payload['file_size'] = $stored['size'];
            $payload['storage_disk'] = $stored['disk'];
        }

        $material->update($payload);

        return back()->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Course $course, Material $material): RedirectResponse
    {
        abort_unless((int) $material->course_id === (int) $course->id, 404);
        Gate::authorize('update', $course);

        $this->fileStorageService->deleteIfExists($material->file_path, $material->storage_disk ?? 'local');
        $material->delete();

        return back()->with('success', 'Materi berhasil dihapus.');
    }
}
