<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ActivityLogService;
use App\Services\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
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

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $this->fileStorageService->deleteIfExists($user->avatar_path, 'public');
            $validated['avatar_path'] = $this->fileStorageService->storePublic($request->file('avatar'), 'avatars');
        }

        $user->update($validated);
        $this->activityLogService->log('profile.updated', "{$user->name} memperbarui profil.", $user);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(UpdateProfilePasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->activityLogService->log('profile.password_changed', "{$user->name} mengubah kata sandi.", $user);

        return back()->with('success', 'Kata sandi berhasil diubah.');
    }
}
