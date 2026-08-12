<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pic\BulkAssignCooperativesRequest;
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

    public function assignments(Request $request, User $pic): Response
    {
        Gate::authorize('pics.assign-cooperatives');
        abort_unless($pic->hasRole('pic_kdkmp'), 404);
        abort_if(! $pic->city_id, 422, 'PIC belum memiliki wilayah kota/kabupaten.');

        $cooperatives = Cooperative::query()
            ->where('city_id', $pic->city_id)
            ->with(['district:id,name', 'village:id,name'])
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($nested) => $nested->whereLike('name', "%{$search}%", caseSensitive: false)->orWhereLike('nik', "%{$search}%", caseSensitive: false)))
            ->when($request->string('assignment')->toString(), function ($query, $assignment) use ($pic) {
                $assignment === 'assigned'
                    ? $query->whereHas('pics', fn ($pics) => $pics->whereKey($pic->id))
                    : $query->whereDoesntHave('pics', fn ($pics) => $pics->whereKey($pic->id));
            })
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        $visibleIds = collect($cooperatives->items())->pluck('id');
        $assignedIds = $pic->assignedCooperatives()
            ->whereIn('cooperatives.id', $visibleIds)
            ->pluck('cooperatives.id');

        return Inertia::render('Pics/Assignments', [
            'pic' => $pic->load('city:id,name'),
            'cooperatives' => $cooperatives,
            'assignedIds' => $assignedIds,
            'filters' => $request->only(['search', 'assignment']),
        ]);
    }

    public function syncAssignments(BulkAssignCooperativesRequest $request, User $pic): RedirectResponse
    {
        $this->pics->syncVisibleAssignments(
            $pic,
            $request->user(),
            $request->validated('cooperative_ids'),
            $request->validated('visible_cooperative_ids'),
        );

        return back()->with('success', 'Assignment koperasi berhasil disimpan.');
    }
}
