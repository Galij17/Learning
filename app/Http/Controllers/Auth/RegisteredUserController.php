<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function create(): Response|RedirectResponse
    {
        if (Setting::getValue('allow_registration', '1') !== '1') {
            return redirect()->route('login')->with('error', 'Pendaftaran akun mandiri sedang ditutup oleh Administrator.');
        }

        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        if (Setting::getValue('allow_registration', '1') !== '1') {
            abort(403, 'Pendaftaran mandiri dinonaktifkan.');
        }

        $user = DB::transaction(function () use ($request) {
            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'identity_number' => $request->input('identity_number'),
                'phone' => $request->input('phone'),
                'password' => Hash::make($request->string('password')->toString()),
                'is_active' => true,
            ]);

            // Never trust a role from the frontend; self-registration always assigns student.
            $studentRole = Role::query()->where('name', User::ROLE_STUDENT)->firstOrFail();
            $user->roles()->sync([$studentRole->id]);

            $this->activityLogService->log('auth.register', "Pendaftaran akun siswa baru: {$user->name}.", $user, [], $user);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('student.dashboard')->with('success', 'Akun berhasil dibuat. Selamat belajar!');
    }
}
