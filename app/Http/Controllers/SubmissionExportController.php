<?php

namespace App\Http\Controllers;

use App\Services\Export\SubmissionExcelExportService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubmissionExportController extends Controller
{
    public function __invoke(SubmissionExcelExportService $export): BinaryFileResponse
    {
        Gate::authorize('submissions.export');
        $path = $export->generate();

        return response()->download($path, 'export-semua-pengajuan-'.now()->format('Ymd-His').'.xlsx')->deleteFileAfterSend(true);
    }
}
