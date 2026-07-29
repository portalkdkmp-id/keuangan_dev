<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('dashboard.view');

        return Inertia::render('Dashboard/Index', [
            'cooperativesCount' => Cooperative::query()->accessibleBy($request->user())->count(),
            'submissionStats' => [
                'draft' => FinancialSubmission::query()->ownedBy($request->user())->where('status', 'draft')->count(),
                'waiting_finance' => FinancialSubmission::query()->ownedBy($request->user())->where('status', 'submitted')->count(),
                'finance_new' => FinancialSubmission::query()->where('status', 'submitted')->count(),
                'finance_review' => FinancialSubmission::query()->where('status', 'finance_review')->count(),
            ],
        ]);
    }
}
