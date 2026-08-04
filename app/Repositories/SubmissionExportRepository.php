<?php

namespace App\Repositories;

use App\Models\FinancialSubmission;
use Closure;

class SubmissionExportRepository
{
    public function eachChunk(Closure $callback): void
    {
        FinancialSubmission::query()
            ->with([
                'submitter:id,name,email', 'submitterCity:id,name', 'cooperative:id,name',
                'requestCategory:id,name', 'requestType:id,name', 'attachments:id,financial_submission_id,original_name,mime_type,size,attachment_type',
                'financeValidator:id,name', 'approvalDecisionMaker:id,name', 'directorDecisionMaker:id,name',
            ])
            ->orderBy('id')
            ->chunkById(500, $callback);
    }
}
