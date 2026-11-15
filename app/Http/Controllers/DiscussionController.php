<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Course;
use App\Models\Discussion;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DiscussionController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(Request $request, Course $course): Response
    {
        Gate::authorize('view', $course);

        $discussions = $course->discussions()
            ->with(['user:id,name,avatar_path', 'user.roles:id,name'])
            ->withCount('allComments')
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->where(fn ($sub) => $sub->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%"));
            })
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Discussions/Index', [
            'course' => $course->load('instructor:id,name'),
            'discussions' => $discussions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('view', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $discussion = Discussion::query()->create([
            'course_id' => $course->id,
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]);

        $this->activityLogService->log('discussion.created', "Membuat topik diskusi: {$discussion->title}", $discussion);

        return redirect()->route('courses.discussions.show', [$course->id, $discussion->id])
            ->with('success', 'Topik diskusi berhasil dibuat.');
    }

    public function show(Course $course, Discussion $discussion): Response
    {
        abort_unless((int) $discussion->course_id === (int) $course->id, 404);
        Gate::authorize('view', $discussion);

        $discussion->load([
            'user:id,name,avatar_path',
            'user.roles:id,name',
            'comments' => fn ($q) => $q->with(['user:id,name,avatar_path', 'user.roles:id,name', 'replies.user:id,name,avatar_path', 'replies.user.roles:id,name']),
        ]);

        return Inertia::render('Discussions/Show', [
            'course' => $course->load('instructor:id,name'),
            'discussion' => $discussion,
        ]);
    }

    public function storeComment(Request $request, Course $course, Discussion $discussion): RedirectResponse
    {
        abort_unless((int) $discussion->course_id === (int) $course->id, 404);
        Gate::authorize('view', $discussion);

        if ($discussion->is_locked && ! Gate::allows('moderate', $discussion)) {
            return back()->with('error', 'Diskusi ini telah dikunci oleh pengajar.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);

        Comment::query()->create([
            'discussion_id' => $discussion->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function togglePin(Course $course, Discussion $discussion): RedirectResponse
    {
        abort_unless((int) $discussion->course_id === (int) $course->id, 404);
        Gate::authorize('moderate', $discussion);

        $discussion->update(['is_pinned' => ! $discussion->is_pinned]);

        return back()->with('success', $discussion->is_pinned ? 'Topik disematkan.' : 'Semat topik dilepas.');
    }

    public function toggleLock(Course $course, Discussion $discussion): RedirectResponse
    {
        abort_unless((int) $discussion->course_id === (int) $course->id, 404);
        Gate::authorize('moderate', $discussion);

        $discussion->update(['is_locked' => ! $discussion->is_locked]);

        return back()->with('success', $discussion->is_locked ? 'Diskusi dikunci.' : 'Diskusi dibuka kembali.');
    }

    public function destroy(Course $course, Discussion $discussion): RedirectResponse
    {
        abort_unless((int) $discussion->course_id === (int) $course->id, 404);
        Gate::authorize('delete', $discussion);

        $discussion->delete();

        return redirect()->route('courses.discussions.index', $course->id)->with('success', 'Topik diskusi dihapus.');
    }

    public function destroyComment(Request $request, Course $course, Discussion $discussion, Comment $comment): RedirectResponse
    {
        abort_unless((int) $discussion->course_id === (int) $course->id && (int) $comment->discussion_id === (int) $discussion->id, 404);
        $canDelete = Gate::allows('moderate', $discussion) || (int) $comment->user_id === (int) $request->user()->id;
        abort_unless($canDelete, 403);

        $comment->delete();

        return back()->with('success', 'Komentar dihapus.');
    }
}
