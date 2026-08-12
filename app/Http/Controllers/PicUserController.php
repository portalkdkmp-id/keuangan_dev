<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pic\BulkAssignCooperativesRequest;
use App\Http\Requests\Pic\StorePicRequest;
use App\Http\Requests\Pic\UpdatePicRequest;
use App\Models\City;
use App\Models\Cooperative;
use App\Models\User;
use App\Services\Pic\PicManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PicUserController extends Controller
{
    public function __construct(private readonly PicManagementService $pics) {}

    public function index(Request $request): Response
    {
        Gate::authorize('pics.view');

        return Inertia::render('Pics/Index', [
            'pics' => $this->pics->paginate($request->only(['search', 'city_id', 'is_active'])),
            'cities' => City::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'city_id', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('pics.create');

        return Inertia::render('Pics/Form', ['pic' => null, 'cities' => City::orderBy('name')->get(['id', 'name'])]);
    }

    public function store(StorePicRequest $request): RedirectResponse
    {
        $this->pics->create($request->validated());

        return to_route('pics.index')->with('success', 'PIC berhasil dibuat.');
    }

    public function edit(User $pic): Response
    {
        Gate::authorize('pics.update');
        abort_unless($pic->hasRole('pic_kdkmp'), 404);

        return Inertia::render('Pics/Form', ['pic' => $pic, 'cities' => City::orderBy('name')->get(['id', 'name'])]);
    }

    public function update(UpdatePicRequest $request, User $pic): RedirectResponse
    {
        $this->pics->update($pic, $request->validated(), $request->user());

        return to_route('pics.index')->with('success', 'PIC berhasil diperbarui.');
    }

    public function destroy(Request $request, User $pic): RedirectResponse
    {
        Gate::authorize('pics.delete');
        $this->pics->delete($pic, $request->user());

        return to_route('pics.index')->with('success', 'PIC berhasil dihapus.');
    }

    public function assignments(Request $request, User $pic): Response
    {
        Gate::authorize('pics.assign-cooperatives');
        abort_unless($pic->hasRole('pic_kdkmp'), 404);
        abort_if(! $pic->city_id, 422, 'PIC belum memiliki wilayah kota/kabupaten.');

        $cooperatives = Cooperative::query()
            ->where('city_id', $pic->city_id)
            ->with(['district:id,name', 'village:id,name'])
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($nested) => $nested->whereLike('name', "%{$search}%", caseSensitive: false)->orWhereLike('nik', "%{$search}%", caseSensitive: false)))
            ->when($request->filled('assignment'), function ($query) use ($request, $pic) {
                $request->string('assignment')->toString() === 'assigned'
                    ? $query->whereHas('pics', fn ($pics) => $pics->whereKey($pic->id))
                    : $query->whereDoesntHave('pics', fn ($pics) => $pics->whereKey($pic->id));
            })
            ->orderBy('name')->paginate(50)->withQueryString();
        $assignedIds = $pic->assignedCooperatives()->whereIn('cooperatives.id', collect($cooperatives->items())->pluck('id'))->pluck('cooperatives.id');

        return Inertia::render('Pics/Assignments', ['pic' => $pic->load('city:id,name'), 'cooperatives' => $cooperatives, 'assignedIds' => $assignedIds, 'filters' => $request->only(['search', 'assignment'])]);
    }

    public function syncAssignments(BulkAssignCooperativesRequest $request, User $pic): RedirectResponse
    {
        $this->pics->syncVisibleAssignments($pic, $request->user(), $request->validated('cooperative_ids'), $request->validated('visible_cooperative_ids'));

        return back()->with('success', 'Assignment koperasi berhasil disimpan.');
    }
}
