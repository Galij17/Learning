<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user?->loadMissing('roles.permissions');

        $logoPath = Setting::getValue('site_logo_path', '');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'identity_number' => $user->identity_number,
                    'phone' => $user->phone,
                    'bio' => $user->bio,
                    'avatar_url' => $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null,
                    'is_active' => $user->is_active,
                    'role' => $user->primaryRole(),
                    'roles' => $user->roles->pluck('name')->values()->all(),
                    'permissions' => $user->roles->flatMap->permissions->pluck('name')->unique()->values()->all(),
                ] : null,
            ],
            'appSettings' => [
                'site_name' => Setting::getValue('site_name', 'VerdantLMS'),
                'site_tagline' => Setting::getValue('site_tagline', 'Platform Manajemen Pembelajaran Terpadu'),
                'institution_name' => Setting::getValue('institution_name', 'Universitas Tawangmangu'),
                'site_logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
                'allow_registration' => Setting::getValue('allow_registration', '1') === '1',
            ],
            'notifications' => fn () => $user ? [
                'unread_count' => $user->unreadNotifications()->count(),
                'recent' => $user->notifications()->latest()->limit(6)->get()->map(fn ($n) => [
                    'id' => $n->id,
                    'data' => $n->data,
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at_human' => $n->created_at?->diffForHumans(),
                ]),
            ] : ['unread_count' => 0, 'recent' => []],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
}
