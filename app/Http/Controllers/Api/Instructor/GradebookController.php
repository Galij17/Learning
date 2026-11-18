<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Models\Course;
use App\Models\Grade;
use App\Services\GradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GradebookController extends Controller
{
    public function __construct(
        private readonly GradingService $gradingService,
    ) {}

    public function gradebook(Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $grades = $course->grades()->with('student:id,name')->get()->map(fn (Grade $g) => (new GradeResource($g))->resolve())->values();

        return response()->json(['data' => $grades]);
    }

    public function updateGradeFeedback(Request $request, Course $course, int $studentId): JsonResponse
    {
        Gate::authorize('update', $course);
        $validated = $request->validate(['instructor_feedback' => ['required', 'string', 'max:3000']]);
        $student = $course->students()->where('users.id', $studentId)->firstOrFail();

        $grade = $this->gradingService->recalculateStudentGrade(
            $course,
            $student,
            $request->user(),
            trim($validated['instructor_feedback'])
        );

        return response()->json(['data' => (new GradeResource($grade))->resolve()]);
    }

    public function recalculateGradebook(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $this->gradingService->recalculateCourseGrades($course, $request->user());

        return response()->json(['message' => 'Gradebook berhasil dihitung ulang.']);
    }
}
