<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RegionController extends Controller
{
    public function provinces(): JsonResponse
    {
        Gate::authorize('regions.view');

        return response()->json(Province::orderBy('name')->get(['id', 'code', 'name']));
    }

    public function cities(Request $request): JsonResponse
    {
        Gate::authorize('regions.view');

        return response()->json(City::where('province_id', $request->query('province_id'))->orderBy('name')->get(['id', 'code', 'full_code', 'name']));
    }

    public function districts(Request $request): JsonResponse
    {
        Gate::authorize('regions.view');

        return response()->json(District::where('city_id', $request->query('city_id'))->orderBy('name')->get(['id', 'code', 'full_code', 'name']));
    }

    public function villages(Request $request): JsonResponse
    {
        Gate::authorize('regions.view');

        return response()->json(Village::where('district_id', $request->query('district_id'))->orderBy('name')->get(['id', 'code', 'full_code', 'name']));
    }
}
