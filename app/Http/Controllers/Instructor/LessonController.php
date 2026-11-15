<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'meeting_number' => ['required', 'integer', 'min:1', 'max:100'],
            'is_published' => ['boolean'],
        ]);

        $course->lessons()->create([
            ...$validated,
            'sort_order' => $course->lessons()->count() + 1,
            'is_published' => $request->boolean('is_published', true),
        ]);

        return back()->with('success', 'Bab / pertemuan berhasil ditambahkan.');
    }

    public function update(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'meeting_number' => ['required', 'integer', 'min:1', 'max:100'],
            'is_published' => ['boolean'],
        ]);

        $lesson->update([
            ...$validated,
            'is_published' => $request->boolean('is_published', true),
        ]);

        return back()->with('success', 'Bab / pertemuan berhasil diperbarui.');
    }

    public function destroy(Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
        Gate::authorize('update', $course);

        $lesson->delete();

        return back()->with('success', 'Bab / pertemuan dihapus.');
    }
}
