<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Course;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class CourseAnnouncementController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:10000'],
            'is_pinned' => ['boolean'],
        ]);

        $announcement = Announcement::query()->create([
            'course_id' => $course->id,
            'author_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => now(),
        ]);

        Notification::send($course->students, new AnnouncementPublishedNotification($announcement));

        return back()->with('success', 'Pengumuman kelas berhasil diterbitkan.');
    }

    public function destroy(Course $course, Announcement $announcement): RedirectResponse
    {
        abort_unless((int) $announcement->course_id === (int) $course->id, 404);
        Gate::authorize('update', $course);

        $announcement->delete();

        return back()->with('success', 'Pengumuman kelas dihapus.');
    }
}
