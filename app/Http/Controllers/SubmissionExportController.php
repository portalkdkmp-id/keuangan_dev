<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Http\Requests\SubmissionExportRequest;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\User;
use App\Repositories\SubmissionExportRepository;
use App\Services\Export\SubmissionExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubmissionExportController extends Controller
{
    public function index(Request $request, SubmissionExportRepository $repository): Response
    {
        Gate::authorize('submissions.export');
        $user = $request->user();
        $filters = $request->only(['status', 'cooperative_id', 'pic_id', 'created_from', 'created_to', 'status_updated_from', 'status_updated_to']);
        $isPic = $user->hasRole('pic_kdkmp');
        $scoped = $repository->query($user, $filters);

        return Inertia::render('Reports/SubmissionExports/Index', [
            'submissions' => (clone $scoped)->with(['submitter:id,name', 'cooperative:id,name'])->latest('created_at')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'isPic' => $isPic,
            'statuses' => collect(SubmissionStatus::cases())->map(fn (SubmissionStatus $status) => ['value' => $status->value, 'label' => str($status->value)->replace('_', ' ')->title()->toString()]),
            'cooperatives' => Cooperative::query()
                ->select(['id', 'name'])
                ->whereHas('financialSubmissions', fn ($query) => $query->when($isPic, fn ($query) => $query->where('submitted_by', $user->id)))
                ->orderBy('name')->get(),
            'pics' => $isPic ? [] : User::role('pic_kdkmp')->select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function download(SubmissionExportRequest $request, SubmissionExcelExportService $export): BinaryFileResponse
    {
        $path = $export->generate($request->user(), $request->validated());

        return $this->fileResponse($path, 'export-laporan-pengajuan');
    }

    public function single(Request $request, FinancialSubmission $financialSubmission, SubmissionExcelExportService $export): BinaryFileResponse
    {
        Gate::authorize('submissions.export');
        abort_if($request->user()->hasRole('pic_kdkmp') && $financialSubmission->submitted_by !== $request->user()->id, 403);

        $path = $export->generate($request->user(), [], $financialSubmission);

        return $this->fileResponse($path, 'pengajuan-'.$financialSubmission->submission_number);
    }

    private function fileResponse(string $path, string $name): BinaryFileResponse
    {
        $safeName = str($name)->replaceMatches('/[^A-Za-z0-9_-]+/', '-')->trim('-');

        return response()->download($path, $safeName.'-'.now()->format('Ymd-His').'.xlsx')->deleteFileAfterSend(true);
    }
}
