<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $roleFilter = $request->string('role')->toString();
        $statusFilter = $request->string('status')->toString();

        $users = User::query()
            ->with('roles:id,name,display_name')
            ->when($search, function ($q) use ($search) {
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identity_number', 'like', "%{$search}%"));
            })
            ->when($roleFilter, function ($q) use ($roleFilter) {
                $q->whereHas('roles', fn ($r) => $r->where('name', $roleFilter));
            })
            ->when($statusFilter !== '', function ($q) use ($statusFilter) {
                if ($statusFilter === 'active') {
                    $q->where('is_active', true);
                } elseif ($statusFilter === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'identity_number' => $u->identity_number,
                'phone' => $u->phone,
                'bio' => $u->bio,
                'is_active' => $u->is_active,
                'role' => $u->primaryRole(),
                'role_label' => $u->roles->first()?->display_name ?? 'Siswa',
                'last_login_at' => $u->last_login_at?->format('d M Y H:i'),
                'created_at' => $u->created_at?->format('d M Y'),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => Role::query()->get(['id', 'name', 'display_name']),
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::query()->create([
                'name' => trim($request->string('name')->toString()),
                'email' => strtolower(trim($request->string('email')->toString())),
                'identity_number' => $request->input('identity_number'),
                'phone' => $request->input('phone'),
                'bio' => $request->input('bio'),
                'is_active' => $request->boolean('is_active', true),
                'email_verified_at' => now(),
                'password' => Hash::make($request->string('password')->toString()),
            ]);

            $role = Role::query()->where('name', $request->string('role')->toString())->firstOrFail();
            $user->roles()->sync([$role->id]);

            $this->activityLogService->log('admin.user_created', "Membuat akun pengguna {$user->name} ({$role->display_name}).", $user);
        });

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $user->update([
                'name' => trim($request->string('name')->toString()),
                'email' => strtolower(trim($request->string('email')->toString())),
                'identity_number' => $request->input('identity_number'),
                'phone' => $request->input('phone'),
                'bio' => $request->input('bio'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            if (! $user->is_active) {
                $user->tokens()->delete();
            }

            $role = Role::query()->where('name', $request->string('role')->toString())->firstOrFail();
            $user->roles()->sync([$role->id]);

            $this->activityLogService->log('admin.user_updated', "Memperbarui data pengguna {$user->name}.", $user);
        });

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) {
            $user->tokens()->delete();
        }
        $statusText = $user->is_active ? 'mengaktifkan' : 'menonaktifkan';
        $this->activityLogService->log('admin.user_toggled', "Admin {$statusText} akun {$user->name}.", $user);

        return back()->with('success', 'Status aktivasi akun berhasil diubah.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);
        $user->tokens()->delete();
        $this->activityLogService->log('admin.password_reset', "Reset kata sandi untuk akun {$user->name}.", $user);

        return back()->with('success', "Kata sandi {$user->name} berhasil direset.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        $user->tokens()->delete();
        $user->delete();
        $this->activityLogService->log('admin.user_deleted', "Menghapus akun pengguna {$user->name}.", $user);

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
