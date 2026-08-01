<?php

namespace App\Http\Controllers;

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
            'types' => SubmissionRequestType::orderBy('sort_order')->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('submission-types.create');

        return Inertia::render('SubmissionMasters/Types/Form', ['type' => null]);
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

        return Inertia::render('SubmissionMasters/Types/Form', ['type' => $submissionType]);
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
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['slug'] = Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
