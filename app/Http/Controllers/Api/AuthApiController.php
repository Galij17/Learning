<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthApiController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->where('email', strtolower(trim($credentials['email'])))
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak sesuai.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Akun Anda saat ini dinonaktifkan. Hubungi Administrator.',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken($credentials['device_name'] ?? 'verdantlms-mobile')->plainTextToken;

        $this->activityLogService->log('auth.api_login', "{$user->name} login melalui aplikasi mobile.", $user, [], $user);

        return response()->json([
            'token' => $token,
            'user' => (new UserResource($user))->resolve(),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        if (Setting::getValue('allow_registration', '1') !== '1') {
            return response()->json([
                'message' => 'Pendaftaran akun mandiri sedang ditutup oleh Administrator.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
            'phone' => ['nullable', 'string', 'max:30'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::query()->create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'is_active' => true,
            ]);

            $studentRole = Role::query()->where('name', User::ROLE_STUDENT)->firstOrFail();
            $user->roles()->syncWithoutDetaching([$studentRole->id]);

            $this->activityLogService->log('auth.api_register', "{$user->name} mendaftar sebagai siswa via mobile.", $user, [], $user);

            return $user;
        });

        $token = $user->createToken($validated['device_name'] ?? 'verdantlms-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => (new UserResource($user))->resolve(),
        ], 201);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => (new UserResource($request->user()))->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        if ($user) {
            $this->activityLogService->log('auth.api_logout', "{$user->name} logout dari aplikasi mobile.", $user, [], $user);
        }

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            Password::sendResetLink(['email' => strtolower(trim($validated['email']))]);
        } catch (\Throwable) {
            // Do not leak email existence or mail server details
        }

        return response()->json([
            'message' => 'Jika email terdaftar, tautan reset password akan dikirimkan.',
        ]);
    }
}
