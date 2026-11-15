<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorageService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function edit(): Response
    {
        return Inertia::render('Profile/Edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'identity_number' => ['nullable', 'string', 'max:50', Rule::unique('users', 'identity_number')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $this->fileStorageService->deleteIfExists($user->avatar_path, 'public');
            $validated['avatar_path'] = $this->fileStorageService->storePublic($request->file('avatar'), 'avatars');
        }

        $user->update($validated);
        $this->activityLogService->log('profile.updated', "{$user->name} memperbarui profil.", $user);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->activityLogService->log('profile.password_changed', "{$user->name} mengubah kata sandi.", $user);

        return back()->with('success', 'Kata sandi berhasil diubah.');
    }
}
