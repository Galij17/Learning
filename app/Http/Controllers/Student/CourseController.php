<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\MaterialProgress;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollmentService) {}

    public function index(Request $request): Response
    {
        $student = $request->user();

        $enrollments = Enrollment::query()
            ->whereHas('course')
            ->where('student_id', $student->id)
            ->whereIn('status', ['active', 'completed'])
            ->with([
                'course:id,title,code,description,instructor_id,category_id,academic_period_id,status',
                'course.instructor:id,name',
                'course.category:id,name',
                'course.academicPeriod:id,name',
            ])
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->whereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Student/Courses/Index', [
            'enrollments' => $enrollments,
            'filters' => $request->only(['search']),
        ]);
    }

    public function join(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $enrollment = $this->enrollmentService->joinByCode($request->user(), $validated['code']);

        return redirect()->route('student.courses.show', $enrollment->course_id)
            ->with('success', 'Berhasil bergabung ke kelas!');
    }

    public function show(Request $request, Course $course): Response
    {
        Gate::authorize('view', $course);
        $student = $request->user();

        $course->load([
            'category:id,name',
            'subject:id,name,code',
            'academicPeriod:id,name',
            'instructor:id,name,email',
            'lessons' => fn ($q) => $q->where('is_published', true)->with([
                'materials' => fn ($m) => $m->where('is_published', true)->orderBy('sort_order'),
            ]),
            'assignments' => fn ($q) => $q->where('is_published', true)->with([
                'submissions' => fn ($s) => $s->where('student_id', $student->id),
            ]),
            'quizzes' => fn ($q) => $q->where('is_published', true)->withCount('questions')->with([
                'attempts' => fn ($a) => $a->where('student_id', $student->id)->orderByDesc('attempt_number'),
            ]),
            'announcements.author:id,name',
        ]);

        $completedMaterialIds = MaterialProgress::query()
            ->where('student_id', $student->id)
            ->where('is_completed', true)
            ->whereHas('material', fn ($q) => $q->where('course_id', $course->id))
            ->pluck('material_id')
            ->all();

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        $grade = Grade::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        $certificate = Certificate::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        return Inertia::render('Student/Courses/Show', [
            'course' => $course,
            'enrollment' => $enrollment,
            'completedMaterialIds' => $completedMaterialIds,
            'grade' => $grade,
            'certificate' => $certificate,
        ]);
    }
}
