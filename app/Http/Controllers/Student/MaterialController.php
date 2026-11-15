<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollmentService) {}

    public function show(Request $request, Course $course, Material $material): Response
    {
        abort_unless((int) $material->course_id === (int) $course->id, 404);
        Gate::authorize('view', $course);

        if (! $material->is_published && $request->user()->isStudent()) {
            abort(403, 'Materi ini belum tersedia.');
        }

        $material->load('lesson:id,title,meeting_number');

        $isCompleted = MaterialProgress::query()
            ->where('material_id', $material->id)
            ->where('student_id', $request->user()->id)
            ->where('is_completed', true)
            ->exists();

        return Inertia::render('Student/Materials/Show', [
            'course' => $course->only(['id', 'title', 'code']),
            'material' => $material,
            'isCompleted' => $isCompleted,
        ]);
    }

    public function complete(Request $request, Course $course, Material $material): RedirectResponse
    {
        abort_unless((int) $material->course_id === (int) $course->id, 404);
        Gate::authorize('view', $course);

        $enrollment = $this->enrollmentService->markMaterialCompleted($material, $request->user());

        return back()->with('success', "Materi ditandai selesai. Progres kelas Anda: {$enrollment->progress_percentage}%.");
    }
}
