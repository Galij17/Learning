<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Http\Resources\MaterialResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Material;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LessonMaterialController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
    ) {}

    public function storeLesson(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'meeting_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'meeting_number' => $validated['meeting_number'] ?? 1,
            'sort_order' => $course->lessons()->count() + 1,
            'is_published' => true,
        ]);

        return response()->json(['data' => (new LessonResource($lesson))->resolve()], 201);
    }

    public function storeMaterial(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'lesson_id' => [
                'required',
                'integer',
                Rule::exists('lessons', 'id')->where('course_id', $course->id)->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:pdf,ppt,docx,image,video,link,text'],
            'content' => ['nullable', 'string', 'max:50000'],
            'video_url' => ['nullable', 'url:http,https', 'max:500'],
            'file' => ['nullable', 'file', 'max:51200', 'mimes:pdf,ppt,pptx,doc,docx,jpg,jpeg,png,webp,mp4,webm'],
        ]);

        if (in_array($validated['type'], ['pdf', 'ppt', 'docx', 'image'], true) && ! $request->hasFile('file')) {
            return response()->json(['message' => 'Berkas materi wajib diunggah untuk tipe materi ini.'], 422);
        }
        if ($validated['type'] === 'video' && ! $request->hasFile('file') && empty($validated['video_url'])) {
            return response()->json(['message' => 'Unggah berkas video atau masukkan URL video.'], 422);
        }
        if ($validated['type'] === 'link' && empty($validated['video_url'])) {
            return response()->json(['message' => 'Tautan referensi wajib diisi.'], 422);
        }
        if ($validated['type'] === 'text' && empty($validated['content'])) {
            return response()->json(['message' => 'Konten bacaan wajib diisi.'], 422);
        }

        $lesson = $course->lessons()->findOrFail($validated['lesson_id']);

        $fileMeta = [];
        if ($request->hasFile('file')) {
            $stored = $this->fileStorage->storePrivate($request->file('file'), "materials/{$course->id}");
            $fileMeta = [
                'storage_disk' => $stored['disk'],
                'file_path' => $stored['path'],
                'file_name' => $stored['name'],
                'mime_type' => $stored['mime'],
                'file_size' => $stored['size'],
            ];
        }

        $material = Material::query()->create(array_merge([
            'lesson_id' => $lesson->id,
            'course_id' => $course->id,
            'title' => trim($validated['title']),
            'type' => $validated['type'],
            'content' => $validated['content'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
            'is_published' => true,
        ], $fileMeta));

        return response()->json(['data' => (new MaterialResource($material))->resolve()], 201);
    }

    public function destroyMaterial(Course $course, Material $material): JsonResponse
    {
        abort_unless((int) $material->course_id === (int) $course->id, 404);
        Gate::authorize('update', $course);

        $this->fileStorage->deleteIfExists($material->file_path, $material->storage_disk ?? 'local');
        $material->delete();

        return response()->json(['message' => 'Materi dihapus.']);
    }
}
