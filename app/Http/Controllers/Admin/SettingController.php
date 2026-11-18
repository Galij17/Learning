<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorageService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function index(): Response
    {
        $logoPath = Setting::getValue('site_logo_path', '');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'site_name' => Setting::getValue('site_name', 'VerdantLMS'),
                'site_tagline' => Setting::getValue('site_tagline', 'Platform Manajemen Pembelajaran Terpadu'),
                'institution_name' => Setting::getValue('institution_name', 'Universitas Tawangmangu'),
                'support_email' => Setting::getValue('support_email', 'akademik@verdantlms.test'),
                'allow_registration' => Setting::getValue('allow_registration', '1') === '1',
                'default_passing_score' => (int) Setting::getValue('default_passing_score', '70'),
                'site_logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            Setting::setValue('site_name', $validated['site_name'], 'branding');
            Setting::setValue('site_tagline', $validated['site_tagline'] ?? '', 'branding');
            Setting::setValue('institution_name', $validated['institution_name'], 'branding');
            Setting::setValue('support_email', $validated['support_email'], 'general');
            Setting::setValue('allow_registration', $request->boolean('allow_registration'), 'auth', 'boolean');
            Setting::setValue('default_passing_score', $validated['default_passing_score'], 'academic', 'integer');

            if ($request->hasFile('site_logo')) {
                $oldLogo = Setting::getValue('site_logo_path', '');
                $this->fileStorageService->deleteIfExists($oldLogo, 'public');
                $path = $this->fileStorageService->storePublic($request->file('site_logo'), 'branding');
                Setting::setValue('site_logo_path', $path, 'branding');
            }

            $this->activityLogService->log('admin.settings_updated', 'Memperbarui konfigurasi dan branding aplikasi.');
        });

        return back()->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }
}
