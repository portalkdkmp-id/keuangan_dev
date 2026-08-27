<?php

namespace App\Repositories;

use App\Models\FinancialSubmission;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class SubmissionExportRepository
{
    public function query(User $user, array $filters = [], ?FinancialSubmission $submission = null): Builder
    {
        $lastStatusUpdate = SubmissionStatusHistory::query()
            ->selectRaw('MAX(created_at)')
            ->whereColumn('financial_submission_id', 'financial_submissions.id');

        return FinancialSubmission::query()
            ->when($user->hasRole('pic_kdkmp'), fn (Builder $query) => $query->where('submitted_by', $user->id))
            ->when($submission, fn (Builder $query) => $query->whereKey($submission->id))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('submission_number', 'like', "%{$search}%")
                ->orWhereHas('items', fn (Builder $items) => $items->where('description', 'like', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['cooperative_id'] ?? null, fn (Builder $query, string $id) => $query->where('cooperative_id', $id))
            ->when(! $user->hasRole('pic_kdkmp') && ($filters['pic_id'] ?? null), fn (Builder $query) => $query->where('submitted_by', $filters['pic_id']))
            ->when($filters['created_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['created_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['status_updated_from'] ?? null, fn (Builder $query, string $date) => $query->whereRaw('DATE(('.$lastStatusUpdate->toSql().')) >= ?', [$date]))
            ->when($filters['status_updated_to'] ?? null, fn (Builder $query, string $date) => $query->whereRaw('DATE(('.$lastStatusUpdate->toSql().')) <= ?', [$date]))
            ->withMax('statusHistories', 'created_at');
    }

    public function eachChunk(User $user, array $filters, Closure $callback, ?FinancialSubmission $submission = null): void
    {
        $this->query($user, $filters, $submission)
            ->with([
                'submitter:id,name,email', 'submitterCity:id,name', 'cooperative:id,name',
                'requestCategory:id,name', 'requestType:id,name', 'attachments:id,financial_submission_id,original_name,mime_type,size,attachment_type',
                'items.requestType:id,name', 'statusHistories.actor:id,name',
                'financeValidator:id,name', 'approvalDecisionMaker:id,name', 'directorDecisionMaker:id,name',
            ])
            ->orderBy('id')
            ->chunkById(500, $callback);
    }
}
