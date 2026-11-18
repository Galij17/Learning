<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

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

        $paginator->getCollection()->transform(fn (User $u) => (new UserResource($u))->resolve());

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

        return response()->json(['data' => (new UserResource($user))->resolve()], 201);
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

        return response()->json(['data' => (new UserResource($user->fresh()))->resolve()]);
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

        return response()->json(['data' => (new UserResource($user->fresh()))->resolve()]);
    }

    public function resetUserPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $user->update(['password' => $validated['password']]);

        return response()->json(['message' => 'Password pengguna direset.']);
    }
}
