<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPeriodController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(): Response
    {
        $periods = AcademicPeriod::query()
            ->withCount('courses')
            ->orderByDesc('start_date')
            ->paginate(12);

        return Inertia::render('Admin/Academic/Periods', [
            'periods' => $periods,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:academic_periods,code'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $isActive = $request->boolean('is_active');
            if ($isActive) {
                AcademicPeriod::query()->update(['is_active' => false]);
            }

            $period = AcademicPeriod::query()->create([
                ...$validated,
                'code' => strtoupper($validated['code']),
                'is_active' => $isActive,
            ]);

            $this->activityLogService->log('admin.period_created', "Membuat periode akademik {$period->name}.", $period);
        });

        return back()->with('success', 'Periode akademik berhasil ditambahkan.');
    }

    public function update(Request $request, AcademicPeriod $period): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', Rule::unique('academic_periods', 'code')->ignore($period->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        DB::transaction(function () use ($request, $validated, $period) {
            $isActive = $request->boolean('is_active');
            if ($isActive) {
                AcademicPeriod::query()->where('id', '!=', $period->id)->update(['is_active' => false]);
            }

            $period->update([
                ...$validated,
                'code' => strtoupper($validated['code']),
                'is_active' => $isActive,
            ]);
        });

        return back()->with('success', 'Periode akademik berhasil diperbarui.');
    }

    public function destroy(AcademicPeriod $period): RedirectResponse
    {
        if ($period->courses()->exists()) {
            return back()->with('error', 'Periode tidak dapat dihapus karena masih memiliki kelas.');
        }

        $period->delete();

        return back()->with('success', 'Periode akademik dihapus.');
    }
}
