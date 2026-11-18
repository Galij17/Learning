<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Grade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function index(Request $request): Response
    {
        $student = $request->user();

        $grades = Grade::query()
            ->whereHas('course')
            ->where('student_id', $student->id)
            ->with([
                'course:id,title,code,instructor_id,assignment_weight,quiz_weight,exam_weight,min_passing_score',
                'course.instructor:id,name',
            ])
            ->latest('updated_at')
            ->get();

        $certificatesByCourse = Certificate::query()
            ->whereHas('course')
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('course_id');

        $rows = $grades->map(fn (Grade $g) => [
            'id' => $g->id,
            'course' => $g->course,
            'assignment_avg' => (float) $g->assignment_avg,
            'quiz_avg' => (float) $g->quiz_avg,
            'exam_avg' => (float) $g->exam_avg,
            'final_score' => (float) $g->final_score,
            'letter_grade' => $g->letter_grade,
            'instructor_feedback' => $g->instructor_feedback,
            'certificate' => $certificatesByCourse->get($g->course_id),
        ]);

        return Inertia::render('Student/Grades/Index', [
            'grades' => $rows,
        ]);
    }
}
