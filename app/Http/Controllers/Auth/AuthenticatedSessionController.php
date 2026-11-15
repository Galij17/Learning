<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda sedang dinonaktifkan. Hubungi Administrator.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        $this->activityLogService->log('auth.login', "{$user->name} masuk ke sistem.", $user, [], $user);

        return redirect()->intended($this->redirectPathFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $this->activityLogService->log('auth.logout', "{$user->name} keluar dari sistem.", $user, [], $user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectPathFor($user): string
    {
        return match ($user->primaryRole()) {
            'admin' => route('admin.dashboard'),
            'instructor' => route('instructor.dashboard'),
            default => route('student.dashboard'),
        };
    }
}
