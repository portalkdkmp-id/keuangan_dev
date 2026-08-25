<?php

namespace App\Http\Controllers;

use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionRequestTypeController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('submission-types.view');

        return Inertia::render('SubmissionMasters/Types/Index', [
            'types' => SubmissionRequestType::with('requestCategory:id,name')->orderBy('sort_order')->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('submission-types.create');

        return Inertia::render('SubmissionMasters/Types/Form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('submission-types.create');
        SubmissionRequestType::create($this->validated($request));

        return to_route('submission-types.index')->with('success', 'Jenis pengajuan berhasil dibuat.');
    }

    public function edit(SubmissionRequestType $submissionType): Response
    {
        Gate::authorize('submission-types.update');

        return Inertia::render('SubmissionMasters/Types/Form', [...$this->formData(), 'type' => $submissionType]);
    }

    public function update(Request $request, SubmissionRequestType $submissionType): RedirectResponse
    {
        Gate::authorize('submission-types.update');
        $submissionType->update($this->validated($request, $submissionType));

        return to_route('submission-types.index')->with('success', 'Jenis pengajuan berhasil diperbarui.');
    }

    public function destroy(SubmissionRequestType $submissionType): RedirectResponse
    {
        Gate::authorize('submission-types.delete');
        $submissionType->delete();

        return back()->with('success', 'Jenis pengajuan berhasil dihapus.');
    }

    private function validated(Request $request, ?SubmissionRequestType $type = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('submission_request_types', 'name')->ignore($type)],
            'submission_request_category_id' => ['nullable', 'uuid', 'exists:submission_request_categories,id'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['slug'] = Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function formData(): array
    {
        return [
            'type' => null,
            'requestCategories' => SubmissionRequestCategory::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ];
    }
}
