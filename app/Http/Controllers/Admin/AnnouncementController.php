<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAnnouncementRequest;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(): Response
    {
        $announcements = Announcement::query()
            ->with('author:id,name')
            ->whereNull('course_id')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(12);

        return Inertia::render('Admin/Announcements/Index', [
            'announcements' => $announcements,
        ]);
    }

    public function store(SaveAnnouncementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $announcement = Announcement::query()->create([
            'course_id' => null,
            'author_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => now(),
        ]);

        $activeUsers = User::query()->where('is_active', true)->where('id', '!=', $request->user()->id)->get();
        Notification::send($activeUsers, new AnnouncementPublishedNotification($announcement));

        $this->activityLogService->log('admin.announcement_created', "Menerbitkan pengumuman global: {$announcement->title}.", $announcement);

        return back()->with('success', 'Pengumuman global berhasil diterbitkan dan notifikasi terkirim.');
    }

    public function update(SaveAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->course_id === null, 404);

        $validated = $request->validated();

        $announcement->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        return back()->with('success', 'Pengumuman global berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->course_id === null, 404);
        $announcement->delete();

        return back()->with('success', 'Pengumuman global berhasil dihapus.');
    }
}
