<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subject;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(Request $request): Response
    {
        $subjects = Subject::query()
            ->with('category:id,name')
            ->withCount('courses')
            ->when($request->string('search')->toString(), fn ($q, $s) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%"))
            ->when($request->integer('category_id'), fn ($q, $catId) => $q->where('category_id', $catId))
            ->orderBy('code')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Academic/Subjects', [
            'subjects' => $subjects,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:40', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $subject = Subject::query()->create([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->activityLogService->log('admin.subject_created', "Menambahkan mata pelajaran {$subject->code} - {$subject->name}.", $subject);

        return back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:40', Rule::unique('subjects', 'code')->ignore($subject->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $subject->update([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->courses()->exists()) {
            return back()->with('error', 'Mata pelajaran tidak dapat dihapus karena masih memiliki kelas aktif.');
        }

        $subject->delete();

        return back()->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
