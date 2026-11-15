<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Category;
use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseManagementController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(Request $request): Response
    {
        $courses = Course::query()
            ->with(['instructor:id,name,email', 'category:id,name', 'subject:id,name,code', 'academicPeriod:id,name'])
            ->withCount(['enrollments', 'lessons', 'assignments', 'quizzes'])
            ->when($request->string('search')->toString(), fn ($q, $s) => $q
                ->where('title', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%"))
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->integer('period_id'), fn ($q, $pid) => $q->where('academic_period_id', $pid))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'instructors' => $instructors,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'category_id']),
            'periods' => AcademicPeriod::orderByDesc('start_date')->get(['id', 'name', 'is_active']),
            'filters' => $request->only(['search', 'status', 'period_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category_id' => ['required', 'exists:categories,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'instructor_id' => ['required', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $course = Course::query()->create([
            ...$validated,
            'slug' => Str::slug($validated['title']) . '-' . Str::lower(Str::random(5)),
            'code' => strtoupper(Str::random(7)),
        ]);

        $this->activityLogService->log('admin.course_created', "Admin membuat kelas {$course->title} ({$course->code}).", $course);

        return back()->with('success', "Kelas {$course->title} berhasil dibuat dengan kode {$course->code}.");
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category_id' => ['required', 'exists:categories,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'instructor_id' => ['required', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $course->update($validated);
        $this->activityLogService->log('admin.course_updated', "Admin memperbarui kelas {$course->title}.", $course);

        return back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();
        $this->activityLogService->log('admin.course_deleted', "Admin menghapus kelas {$course->title}.", $course);

        return back()->with('success', 'Kelas berhasil dihapus.');
    }
}
