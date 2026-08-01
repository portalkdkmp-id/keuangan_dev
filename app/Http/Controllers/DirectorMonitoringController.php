<?php

namespace App\Http\Controllers;

use App\Services\Monitoring\DirectorMonitoringService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DirectorMonitoringController extends Controller
{
    public function __construct(private readonly DirectorMonitoringService $monitoring) {}

    public function __invoke(): Response
    {
        Gate::authorize('director-monitoring.view');

        return Inertia::render('Director/Dashboard', [
            'summary' => $this->monitoring->summary(),
            'actionable' => $this->monitoring->actionable(),
        ]);
    }
}
