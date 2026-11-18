<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreCourseRequest;
use App\Models\AcademicPeriod;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\FileStorageService;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorageService,
        private readonly ActivityLogService $activityLogService,
        private readonly GradingService $gradingService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $courses = Course::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('instructor_id', $user->id))
            ->with(['category:id,name', 'subject:id,name,code', 'academicPeriod:id,name', 'instructor:id,name'])
            ->withCount(['enrollments', 'lessons', 'assignments', 'quizzes'])
            ->when($request->string('search')->toString(), function ($q, $s) {
                $q->where(fn ($sub) => $sub->where('title', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Instructor/Courses/Index', [
            'courses' => $courses,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'category_id']),
            'periods' => AcademicPeriod::orderByDesc('start_date')->get(['id', 'name', 'is_active']),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['instructor_id'] = $request->user()->isAdmin() && $request->filled('instructor_id')
            ? $request->integer('instructor_id')
            : $request->user()->id;
        $data['slug'] = Str::slug($data['title']) . '-' . Str::lower(Str::random(5));
        $data['code'] = strtoupper(Str::random(7));
        $data['certificate_enabled'] = $request->boolean('certificate_enabled', true);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = $this->fileStorageService->storePublic($request->file('thumbnail'), 'course-thumbnails');
        }

        $course = Course::query()->create($data);
        $this->activityLogService->log('instructor.course_created', "Membuat kelas {$course->title} ({$course->code}).", $course);

        return redirect()->route('instructor.courses.show', $course->id)
            ->with('success', "Kelas berhasil dibuat dengan kode gabung: {$course->code}");
    }

    public function show(Course $course): Response
    {
        Gate::authorize('update', $course);

        $course->load([
            'category:id,name',
            'subject:id,name,code',
            'academicPeriod:id,name',
            'instructor:id,name,email',
            'lessons.materials',
            'assignments' => fn ($q) => $q->withCount('submissions'),
            'quizzes' => fn ($q) => $q->withCount(['questions', 'attempts']),
            'announcements.author:id,name',
            'enrollments.student:id,name,email,identity_number',
        ]);

        $availableStudents = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
            ->where('is_active', true)
            ->whereNotIn('id', $course->enrollments->pluck('student_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'identity_number']);

        return Inertia::render('Instructor/Courses/Show', [
            'course' => $course,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'category_id']),
            'periods' => AcademicPeriod::orderByDesc('start_date')->get(['id', 'name', 'is_active']),
            'availableStudents' => $availableStudents,
        ]);
    }

    public function update(StoreCourseRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $data = $request->validated();
        $data['certificate_enabled'] = $request->boolean('certificate_enabled', true);
        if (! $request->user()->isAdmin()) {
            unset($data['instructor_id']);
        }

        if ($request->hasFile('thumbnail')) {
            $this->fileStorageService->deleteIfExists($course->thumbnail_path, 'public');
            $data['thumbnail_path'] = $this->fileStorageService->storePublic($request->file('thumbnail'), 'course-thumbnails');
        }

        $course->update($data);
        $this->gradingService->recalculateCourseGrades($course, $request->user());
        $this->activityLogService->log('instructor.course_updated', "Memperbarui kelas {$course->title}.", $course);

        return back()->with('success', 'Pengaturan kelas berhasil diperbarui.');
    }

    public function regenerateCode(Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $course->update(['code' => strtoupper(Str::random(7))]);
        $this->activityLogService->log('instructor.course_code_regenerated', "Membuat ulang kode kelas {$course->title} menjadi {$course->code}.", $course);

        return back()->with('success', "Kode kelas baru: {$course->code}");
    }

    public function enrollStudent(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manageStudents', $course);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id,deleted_at,NULL'],
        ]);

        $student = User::query()
            ->whereKey($validated['student_id'])
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_STUDENT))
            ->first();

        if (! $student) {
            return back()->with('error', 'Peserta yang dipilih harus akun siswa aktif.');
        }

        $existing = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            if ($existing->status === 'dropped') {
                $existing->update(['status' => 'active']);
            }
        } else {
            Enrollment::query()->create([
                'course_id' => $course->id,
                'student_id' => $student->id,
                'status' => 'active',
                'enrolled_at' => now(),
            ]);
        }

        return back()->with('success', 'Peserta berhasil ditambahkan ke kelas.');
    }

    public function removeStudent(Course $course, User $student): RedirectResponse
    {
        Gate::authorize('manageStudents', $course);

        Enrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->delete();

        return back()->with('success', 'Peserta dikeluarkan dari kelas.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $course->delete();
        $this->activityLogService->log('instructor.course_deleted', "Menghapus kelas {$course->title}.", $course);

        return redirect()->route('instructor.courses.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
