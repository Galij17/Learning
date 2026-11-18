<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\LessonResource;
use App\Http\Resources\QuizResource;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function courses(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = $request->string('search')->toString();

        $courses = Course::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('instructor_id', $user->id))
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->withCount(['enrollments', 'materials'])
            ->with(['category:id,name', 'subject:id,name', 'instructor:id,name'])
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
            'description' => ['nullable', 'string'],
        ]);

        $catName = trim($validated['category_name'] ?? 'Umum');
        $category = Category::query()->firstOrCreate(
            ['name' => $catName],
            ['slug' => Str::slug($catName) . '-' . Str::lower(Str::random(4))]
        );

        $course = Course::query()->create([
            'category_id' => $category->id,
            'instructor_id' => $request->user()->id,
            'title' => trim($validated['title']),
            'slug' => Str::slug($validated['title']) . '-' . Str::lower(Str::random(5)),
            'code' => strtoupper(Str::random(3)) . '-' . random_int(100, 999),
            'description' => $validated['description'] ?? null,
            'status' => 'published',
        ]);

        return response()->json(['data' => (new CourseResource($course))->resolve()], 201);
    }

    public function showCourse(Course $course): JsonResponse
    {
        Gate::authorize('update', $course);

        $students = $course->enrollments()
            ->with('student:id,name,email')
            ->get()
            ->map(fn (Enrollment $e) => [
                'student_id' => $e->student_id,
                'name' => $e->student?->name ?? '',
                'email' => $e->student?->email ?? '',
                'progress_percentage' => (float) $e->progress_percentage,
                'final_score' => $e->final_score !== null ? (float) $e->final_score : null,
            ])->values();

        return response()->json([
            'course' => (new CourseResource($course))->resolve(),
            'students' => $students,
            'lessons' => $course->lessons()->with('materials')->get()->map(fn (Lesson $l) => (new LessonResource($l))->resolve())->values(),
            'assignments' => $course->assignments()->withCount('submissions')->get()->map(fn (Assignment $a) => (new AssignmentResource($a))->resolve())->values(),
            'quizzes' => $course->quizzes()->withCount('questions')->get()->map(fn (Quiz $q) => (new QuizResource($q))->resolve())->values(),
            'announcements' => $course->announcements()->get()->map(fn (Announcement $a) => (new AnnouncementResource($a))->resolve())->values(),
        ]);
    }

    public function regenerateCode(Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $code = strtoupper(Str::random(3)) . '-' . random_int(100, 999);
        $course->update(['code' => $code]);

        return response()->json(['code' => $code]);
    }

    public function storeAnnouncement(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $ann = Announcement::query()->create([
            'course_id' => $course->id,
            'author_id' => $request->user()->id,
            'title' => trim($validated['title']),
            'content' => trim($validated['content']),
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'published_at' => now(),
        ]);

        return response()->json(['data' => (new AnnouncementResource($ann))->resolve()], 201);
    }
}
