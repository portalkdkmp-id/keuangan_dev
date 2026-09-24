<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pic\BulkAssignCooperativesRequest;
use App\Http\Requests\Pic\ImportPicsRequest;
use App\Http\Requests\Pic\StorePicRequest;
use App\Http\Requests\Pic\UpdatePicRequest;
use App\Models\City;
use App\Models\Cooperative;
use App\Models\Province;
use App\Models\User;
use App\Services\Pic\PicImportService;
use App\Services\Pic\PicManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function importForm(): Response
    {
        Gate::authorize('pics.create');

        return Inertia::render('Pics/Import');
    }

    public function import(ImportPicsRequest $request, PicImportService $importer): RedirectResponse
    {
        $result = $importer->import($request->file('file')->getRealPath(), $request->boolean('dry_run'));
        $stats = $result['stats'];

        return back()->with($stats['failed'] > 0 ? 'warning' : 'success', sprintf(
            'Import selesai. Ditambahkan=%d Diperbarui=%d Gagal=%d',
            $stats['inserted'],
            $stats['updated'],
            $stats['failed'],
        ));
    }

    public function importTemplate(): BinaryFileResponse
    {
        Gate::authorize('pics.create');

        return response()->download(resource_path('templates/template-import-pic-kdkmp.xlsx'));
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
        $pic->loadMissing('city:id,province_id,name');

        $cooperatives = Cooperative::query()
            ->where('city_id', $pic->city_id)
            ->with(['district:id,name', 'village:id,name'])
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($nested) => $nested->whereLike('name', "%{$search}%", caseSensitive: false)->orWhereLike('nik', "%{$search}%", caseSensitive: false)))
            ->when($request->filled('assignment'), function ($query) use ($request, $pic) {
                $request->string('assignment')->toString() === 'assigned'
                    ? $query->whereHas('pics', fn ($pics) => $pics->whereKey($pic->id))
                    : $query->whereDoesntHave('pics', fn ($pics) => $pics->whereKey($pic->id));
            })
            ->when($request->filled('province_id'), fn ($query) => $query->where('province_id', $request->input('province_id')))
            ->when($request->filled('city_id'), fn ($query) => $query->where('city_id', $request->input('city_id')))
            ->when($request->filled('district_id'), fn ($query) => $query->where('district_id', $request->input('district_id')))
            ->when($request->filled('village_id'), fn ($query) => $query
                ->where('village_id', $request->input('village_id'))
                ->when($request->filled('district_id'), fn ($nested) => $nested->where('district_id', $request->input('district_id'))))
            ->orderBy('name')->paginate(50)->withQueryString();
        $assignedIds = $pic->assignedCooperatives()->whereIn('cooperatives.id', collect($cooperatives->items())->pluck('id'))->pluck('cooperatives.id');

        $regions = Province::query()
            ->whereKey($pic->city->province_id)
            ->with(['cities' => fn ($query) => $query
                ->whereKey($pic->city_id)
                ->with(['districts' => fn ($districts) => $districts
                    ->whereHas('villages', fn ($villages) => $villages->whereHas('cooperatives'))
                    ->with(['villages' => fn ($villages) => $villages
                        ->whereHas('cooperatives')
                        ->orderBy('name')
                        ->select(['id', 'district_id', 'name'])])
                    ->orderBy('name')
                    ->select(['id', 'city_id', 'name'])])
                ->select(['id', 'province_id', 'name'])])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Pics/Assignments', ['pic' => $pic, 'cooperatives' => $cooperatives, 'assignedIds' => $assignedIds, 'regions' => $regions, 'filters' => $request->only(['search', 'assignment', 'province_id', 'city_id', 'district_id', 'village_id'])]);
    }

    public function syncAssignments(BulkAssignCooperativesRequest $request, User $pic): RedirectResponse
    {
        $this->pics->syncVisibleAssignments($pic, $request->user(), $request->validated('cooperative_ids'), $request->validated('visible_cooperative_ids'));

        return back()->with('success', 'Assignment koperasi berhasil disimpan.');
    }
}
