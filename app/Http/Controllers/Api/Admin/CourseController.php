<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function courses(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $courses = Course::query()
            ->withCount(['enrollments', 'materials'])
            ->with(['category:id,name', 'subject:id,name', 'instructor:id,name'])
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->latest()
            ->get()
            ->map(fn (Course $c) => (new CourseResource($c))->resolve())
            ->values();

        return response()->json(['data' => $courses]);
    }

    public function storeCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category_name' => ['nullable', 'string', 'max:100'],
            'instructor_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $instructor = User::query()->findOrFail($validated['instructor_id']);
        if (! $instructor->hasAnyRole([User::ROLE_INSTRUCTOR, User::ROLE_ADMIN]) || ! $instructor->is_active) {
            return response()->json(['message' => 'Pengajar yang dipilih harus akun aktif dengan peran pengajar/admin.'], 422);
        }

        $course = DB::transaction(function () use ($validated, $instructor) {
            $catName = trim($validated['category_name'] ?? 'Umum');
            $category = Category::query()->firstOrCreate(
                ['name' => $catName],
                ['slug' => Str::slug($catName) . '-' . Str::lower(Str::random(4))]
            );

            return Course::query()->create([
                'category_id' => $category->id,
                'instructor_id' => $instructor->id,
                'title' => trim($validated['title']),
                'slug' => Str::slug($validated['title']) . '-' . Str::lower(Str::random(5)),
                'code' => strtoupper(Str::random(3)) . '-' . random_int(100, 999),
                'description' => $validated['description'] ?? null,
                'status' => 'published',
            ]);
        });

        return response()->json(['data' => (new CourseResource($course))->resolve()], 201);
    }

    public function destroyCourse(Course $course): JsonResponse
    {
        $course->delete();
        $this->activityLogService->log('admin.course_deleted', "Admin menghapus kelas {$course->title}.", $course);

        return response()->json(['message' => 'Kursus dihapus.']);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => Category::query()->orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:categories,name']]);
        $cat = Category::query()->create([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(4)),
        ]);

        return response()->json(['data' => $cat], 201);
    }
}
