<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cooperative\StoreCooperativeRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeRequest;
use App\Models\Cooperative;
use App\Models\Province;
use App\Models\User;
use App\Services\Cooperative\CooperativeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeController extends Controller
{
    public function __construct(private readonly CooperativeService $cooperatives) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Cooperative::class);

        return Inertia::render('Cooperatives/Index', [
            'cooperatives' => $this->cooperatives->paginate($request->user(), $request->only(['search', 'province_id', 'city_id', 'district_id', 'village_id', 'is_active'])),
            'provinces' => Province::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'province_id', 'city_id', 'district_id', 'village_id', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Cooperative::class);

        return Inertia::render('Cooperatives/Create', ['provinces' => Province::orderBy('name')->get(['id', 'name'])]);
    }

    public function store(StoreCooperativeRequest $request): RedirectResponse
    {
        $this->cooperatives->create($request->validated());

        return to_route('cooperatives.index')->with('success', 'Koperasi berhasil dibuat.');
    }

    public function show(Request $request, Cooperative $cooperative): Response
    {
        Gate::authorize('view', $cooperative);
        $cooperative->load(['province', 'city', 'district', 'village', 'pics.roles']);

        return Inertia::render('Cooperatives/Show', [
            'cooperative' => $cooperative,
            'availablePics' => User::role('pic_kdkmp')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function edit(Cooperative $cooperative): Response
    {
        Gate::authorize('update', $cooperative);
        $cooperative->load(['province', 'city', 'district', 'village']);

        return Inertia::render('Cooperatives/Edit', ['cooperative' => $cooperative, 'provinces' => Province::orderBy('name')->get(['id', 'name'])]);
    }

    public function update(UpdateCooperativeRequest $request, Cooperative $cooperative): RedirectResponse
    {
        $this->cooperatives->update($cooperative, $request->validated());

        return to_route('cooperatives.index')->with('success', 'Koperasi berhasil diperbarui.');
    }

    public function destroy(Cooperative $cooperative): RedirectResponse
    {
        Gate::authorize('delete', $cooperative);
        $this->cooperatives->delete($cooperative);

        return to_route('cooperatives.index')->with('success', 'Koperasi berhasil dihapus.');
    }
}
