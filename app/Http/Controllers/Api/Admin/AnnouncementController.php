<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function announcements(): JsonResponse
    {
        $items = Announcement::query()
            ->whereNull('course_id')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (Announcement $a) => (new AnnouncementResource($a))->resolve())
            ->values();

        return response()->json(['data' => $items]);
    }

    public function storeAnnouncement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $ann = Announcement::query()->create([
            'course_id' => null,
            'author_id' => $request->user()->id,
            'title' => trim($validated['title']),
            'content' => trim($validated['content']),
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'published_at' => now(),
        ]);

        return response()->json(['data' => (new AnnouncementResource($ann))->resolve()], 201);
    }
}
