<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\Material;
use App\Models\Submission;
use App\Services\ActivityLogService;
use App\Services\FileStorageService;
use App\Services\ReportExportService;
use App\Support\ApiPayloadMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SharedApiController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
        private readonly ReportExportService $reportExportService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $this->fileStorage->deleteIfExists($user->avatar_path, 'public');
            $user->avatar_path = $this->fileStorage->storePublic($request->file('avatar'), 'avatars');
        }

        $user->fill([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ])->save();

        return response()->json([
            'user' => ApiPayloadMapper::user($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai.',
                'errors' => ['current_password' => ['Password saat ini tidak sesuai.']],
            ], 422);
        }

        $user->update(['password' => $validated['password']]);

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }

    public function notifications(Request $request): JsonResponse
    {
        $items = $request->user()
            ->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($n) => ApiPayloadMapper::notification($n))
            ->values();

        return response()->json(['data' => $items]);
    }

    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['message' => 'Notifikasi ditandai terbaca.']);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Semua notifikasi ditandai terbaca.']);
    }

    public function deleteNotification(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->delete();

        return response()->json(['message' => 'Notifikasi dihapus.']);
    }

    public function certificates(Request $request): JsonResponse
    {
        $items = Certificate::query()
            ->whereHas('course')
            ->where('student_id', $request->user()->id)
            ->latest('issued_at')
            ->get()
            ->map(fn (Certificate $c) => ApiPayloadMapper::certificate($c))
            ->values();

        return response()->json(['data' => $items]);
    }

    public function certificateShow(Certificate $certificate): JsonResponse
    {
        Gate::authorize('view', $certificate);

        return response()->json(['data' => ApiPayloadMapper::certificate($certificate)]);
    }

    public function certificateDownload(Certificate $certificate): HttpResponse
    {
        Gate::authorize('view', $certificate);

        return $this->reportExportService->downloadCertificatePdf($certificate);
    }

    public function certificateVerify(string $hash): JsonResponse
    {
        $certificate = Certificate::query()->where('verification_hash', $hash)->first();
        if (! $certificate) {
            return response()->json(['message' => 'Sertifikat tidak ditemukan.'], 404);
        }

        return response()->json(['data' => ApiPayloadMapper::certificate($certificate)]);
    }

    public function discussionsIndex(Course $course): JsonResponse
    {
        Gate::authorize('view', $course);

        $items = $course->discussions()
            ->withCount('allComments')
            ->get()
            ->map(fn (Discussion $d) => ApiPayloadMapper::discussion($d))
            ->values();

        return response()->json(['data' => $items]);
    }

    public function discussionsStore(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('view', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $discussion = Discussion::query()->create([
            'course_id' => $course->id,
            'user_id' => $request->user()->id,
            'title' => trim($validated['title']),
            'body' => trim($validated['body']),
        ]);

        $this->activityLogService->log('discussion.created', "Membuat topik diskusi: {$discussion->title}", $discussion);

        return response()->json(['data' => ApiPayloadMapper::discussion($discussion)], 201);
    }

    public function discussionsShow(Course $course, Discussion $discussion): JsonResponse
    {
        abort_unless((int) $discussion->course_id === (int) $course->id, 404);
        Gate::authorize('view', $discussion);

        return response()->json(['data' => ApiPayloadMapper::discussion($discussion, true)]);
    }

    public function discussionsComment(Request $request, Course $course, Discussion $discussion): JsonResponse
    {
        abort_unless((int) $discussion->course_id === (int) $course->id, 404);
        Gate::authorize('view', $discussion);

        if ($discussion->is_locked && ! Gate::allows('moderate', $discussion)) {
            return response()->json(['message' => 'Diskusi ini telah dikunci oleh pengajar.'], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                Rule::exists('comments', 'id')->where('discussion_id', $discussion->id)->whereNull('deleted_at'),
            ],
        ]);

        $comment = Comment::query()->create([
            'discussion_id' => $discussion->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => trim($validated['body']),
        ]);

        return response()->json(['data' => ApiPayloadMapper::comment($comment)], 201);
    }

    public function materialDownload(Request $request, Material $material): StreamedResponse
    {
        $material->loadMissing('course');
        Gate::authorize('view', $material->course);

        if (! $material->is_published && $request->user()->isStudent()) {
            abort(403, 'Materi ini belum dipublikasikan.');
        }

        abort_unless((bool) $material->file_path, 404, 'Berkas materi tidak tersedia.');

        return $this->fileStorage->downloadPrivate(
            $material->file_path,
            $material->file_name ?? 'materi',
            $material->storage_disk ?? 'local'
        );
    }

    public function materialStream(Request $request, Material $material): StreamedResponse
    {
        $material->loadMissing('course');
        Gate::authorize('view', $material->course);

        if (! $material->is_published && $request->user()->isStudent()) {
            abort(403, 'Materi ini belum dipublikasikan.');
        }

        abort_unless((bool) $material->file_path, 404, 'Berkas materi tidak tersedia.');

        return $this->fileStorage->streamPrivate(
            $material->file_path,
            $material->mime_type ?? 'application/octet-stream',
            $material->storage_disk ?? 'local'
        );
    }

    public function assignmentAttachment(Assignment $assignment): StreamedResponse
    {
        Gate::authorize('view', $assignment);
        abort_unless((bool) $assignment->attachment_path, 404, 'Lampiran tugas tidak tersedia.');

        return $this->fileStorage->downloadPrivate(
            $assignment->attachment_path,
            $assignment->attachment_name ?? 'lampiran-tugas'
        );
    }

    public function submissionDownload(Submission $submission): StreamedResponse
    {
        $user = request()->user();
        $course = $submission->assignment->course;
        $canDownload = $user->isAdmin()
            || (int) $submission->student_id === (int) $user->id
            || (int) $course->instructor_id === (int) $user->id;
        abort_unless($canDownload, 403);
        abort_unless((bool) $submission->file_path, 404, 'File pengumpulan tidak tersedia.');

        return $this->fileStorage->downloadPrivate(
            $submission->file_path,
            $submission->file_name ?? 'submission'
        );
    }
}
