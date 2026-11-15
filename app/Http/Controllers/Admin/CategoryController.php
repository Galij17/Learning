<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function index(Request $request): Response
    {
        $categories = Category::query()
            ->withCount(['subjects', 'courses'])
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Academic/Categories', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $category = Category::query()->create([
            ...$validated,
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(4)),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->activityLogService->log('admin.category_created', "Menambahkan kategori {$category->name}.", $category);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $category->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->activityLogService->log('admin.category_updated', "Memperbarui kategori {$category->name}.", $category);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->courses()->exists() || $category->subjects()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh mata pelajaran atau kelas.');
        }

        $category->delete();
        $this->activityLogService->log('admin.category_deleted', "Menghapus kategori {$category->name}.", $category);

        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
