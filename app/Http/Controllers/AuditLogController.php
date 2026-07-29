<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('audit-logs.view');

        return Inertia::render('AuditLogs/Index', [
            'logs' => ActivityLog::query()->with('user:id,name,email')->latest('created_at')->paginate(20)->withQueryString(),
        ]);
    }
}
