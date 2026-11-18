<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Submission;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\ReportExportService;
use App\Support\ApiPayloadMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminApiController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly ReportExportService $reportExportService
    ) {}

    public function dashboard(): JsonResponse
    {
        $activityByDay = ActivityLog::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        $userStats = collect(range(6, 0))->map(function (int $offset) use ($activityByDay) {
            $day = Carbon::now()->subDays($offset);

            return [
                'label' => $day->format('D'),
                'count' => (int) ($activityByDay[$day->toDateString()] ?? 0),
            ];
        })->values();

        return response()->json([
            'total_users' => User::query()->count(),
            'total_students' => User::query()->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_STUDENT))->count(),
            'total_teachers' => User::query()->whereHas('roles', fn ($q) => $q->where('name', User::ROLE_INSTRUCTOR))->count(),
            'total_courses' => Course::query()->count(),
            'active_classes' => Course::query()->where('status', 'published')->count(),
            'total_assignments' => Assignment::query()->count(),
            'recent_activities' => ActivityLog::query()->latest()->limit(8)->pluck('description')->values(),
            'user_stats' => $userStats,
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $role = $request->string('role')->toString();

        $paginator = User::query()
            ->with('roles.permissions')
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($role !== '', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->latest()
            ->paginate(15);

        $paginator->getCollection()->transform(fn (User $u) => ApiPayloadMapper::user($u));

        return response()->json($paginator);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,instructor,student'],
            'password' => ['required', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::query()->create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $roleModel = Role::query()->where('name', $validated['role'])->firstOrFail();
            $user->roles()->sync([$roleModel->id]);

            $this->activityLogService->log('admin.user_created', "Membuat pengguna {$user->name} ({$validated['role']}).", $user);

            return $user;
        });

        return response()->json(['data' => ApiPayloadMapper::user($user)], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'role' => ['required', 'in:admin,instructor,student'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($validated, $user) {
            $user->update([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'] ?? null,
            ]);

            $roleModel = Role::query()->where('name', $validated['role'])->firstOrFail();
            $user->roles()->sync([$roleModel->id]);

            $this->activityLogService->log('admin.user_updated', "Memperbarui pengguna {$user->name}.", $user);
        });

        return response()->json(['data' => ApiPayloadMapper::user($user->fresh())]);
    }

    public function toggleUserStatus(Request $request, User $user): JsonResponse
    {
        if ((int) $user->id === (int) $request->user()->id) {
            return response()->json(['message' => 'Tidak dapat menonaktifkan akun Anda sendiri.'], 422);
        }

        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json(['data' => ApiPayloadMapper::user($user->fresh())]);
    }

    public function resetUserPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $user->update(['password' => $validated['password']]);

        return response()->json(['message' => 'Password pengguna direset.']);
    }

    public function courses(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $courses = Course::query()
            ->withCount(['enrollments', 'materials'])
            ->with(['category:id,name', 'subject:id,name', 'instructor:id,name'])
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->latest()
            ->get()
            ->map(fn (Course $c) => ApiPayloadMapper::course($c))
            ->values();

        return response()->json(['data' => $courses]);
    }

    public function storeCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category_name' => ['nullable', 'string', 'max:100'],
            'instructor_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $instructor = User::query()->findOrFail($validated['instructor_id']);
        if (! $instructor->hasAnyRole([User::ROLE_INSTRUCTOR, User::ROLE_ADMIN]) || ! $instructor->is_active) {
            return response()->json(['message' => 'Pengajar yang dipilih harus akun aktif dengan peran pengajar/admin.'], 422);
        }

        $course = DB::transaction(function () use ($validated, $instructor) {
            $catName = trim($validated['category_name'] ?? 'Umum');
            $category = Category::query()->firstOrCreate(
                ['name' => $catName],
                ['slug' => Str::slug($catName) . '-' . Str::lower(Str::random(4))]
            );

            return Course::query()->create([
                'category_id' => $category->id,
                'instructor_id' => $instructor->id,
                'title' => trim($validated['title']),
                'slug' => Str::slug($validated['title']) . '-' . Str::lower(Str::random(5)),
                'code' => strtoupper(Str::random(3)) . '-' . random_int(100, 999),
                'description' => $validated['description'] ?? null,
                'status' => 'published',
            ]);
        });

        return response()->json(['data' => ApiPayloadMapper::course($course)], 201);
    }

    public function destroyCourse(Course $course): JsonResponse
    {
        $course->delete();
        $this->activityLogService->log('admin.course_deleted', "Admin menghapus kelas {$course->title}.", $course);

        return response()->json(['message' => 'Kursus dihapus.']);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => Category::query()->orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:categories,name']]);
        $cat = Category::query()->create([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(4)),
        ]);

        return response()->json(['data' => $cat], 201);
    }

    public function reports(): JsonResponse
    {
        $avgByCourse = Grade::query()
            ->selectRaw('course_id, AVG(final_score) as avg_score')
            ->groupBy('course_id')
            ->pluck('avg_score', 'course_id');

        $courses = Course::query()->withCount('enrollments')->get()->map(function (Course $c) use ($avgByCourse) {
            return [
                'title' => $c->title,
                'enrollments' => (int) $c->enrollments_count,
                'avg_score' => round((float) ($avgByCourse[$c->id] ?? 0.0), 1),
            ];
        })->values();

        return response()->json([
            'active_users_count' => User::query()->where('is_active', true)->count(),
            'published_courses_count' => Course::query()->where('status', 'published')->count(),
            'submissions_count' => Submission::query()->count(),
            'average_platform_score' => round((float) (Grade::query()->avg('final_score') ?? 0.0), 1),
            'course_reports' => $courses,
        ]);
    }

    public function exportPdf(Request $request): HttpResponse
    {
        return $this->reportExportService->exportPdf(
            $request->integer('course_id') ?: null,
            $request->integer('period_id') ?: null
        );
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        return $this->reportExportService->exportExcel(
            $request->integer('course_id') ?: null,
            $request->integer('period_id') ?: null
        );
    }

    public function announcements(): JsonResponse
    {
        $items = Announcement::query()
            ->whereNull('course_id')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (Announcement $a) => ApiPayloadMapper::announcement($a))
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

        return response()->json(['data' => ApiPayloadMapper::announcement($ann)], 201);
    }

    public function activityLogs(): JsonResponse
    {
        $items = ActivityLog::query()
            ->with('user:id,name')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (ActivityLog $l) => [
                'id' => $l->id,
                'action' => $l->action,
                'description' => $l->description,
                'actor_name' => $l->user?->name ?? 'System',
                'created_at' => $l->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json(['data' => $items]);
    }
}
