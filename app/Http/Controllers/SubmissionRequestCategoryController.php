<?php

namespace App\Http\Controllers;

use App\Models\SubmissionRequestCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionRequestCategoryController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('submission-masters.view');

        return Inertia::render('SubmissionMasters/Categories/Index', [
            'categories' => SubmissionRequestCategory::orderBy('sort_order')->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('submission-masters.create');

        return Inertia::render('SubmissionMasters/Categories/Form', ['category' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('submission-masters.create');
        SubmissionRequestCategory::create($this->validated($request));

        return to_route('submission-categories.index')->with('success', 'Kategori pengajuan berhasil dibuat.');
    }

    public function edit(SubmissionRequestCategory $submissionCategory): Response
    {
        Gate::authorize('submission-masters.update');

        return Inertia::render('SubmissionMasters/Categories/Form', ['category' => $submissionCategory]);
    }

    public function update(Request $request, SubmissionRequestCategory $submissionCategory): RedirectResponse
    {
        Gate::authorize('submission-masters.update');
        $submissionCategory->update($this->validated($request, $submissionCategory));

        return to_route('submission-categories.index')->with('success', 'Kategori pengajuan berhasil diperbarui.');
    }

    public function destroy(SubmissionRequestCategory $submissionCategory): RedirectResponse
    {
        Gate::authorize('submission-masters.delete');
        $submissionCategory->delete();

        return back()->with('success', 'Kategori pengajuan berhasil dihapus.');
    }

    private function validated(Request $request, ?SubmissionRequestCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('submission_request_categories', 'name')->ignore($category)],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['slug'] = Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
