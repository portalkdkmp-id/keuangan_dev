<?php

namespace App\Services\Submission;

use App\Enums\RevisionRequestStatus;
use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use App\Models\SubmissionCategory;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Notifications\SubmissionResubmittedNotification;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmissionRevisionService
{
    public function __construct(
        private readonly SubmissionItemService $items,
        private readonly SubmissionStatusService $statuses,
        private readonly AuditLogService $auditLog,
    ) {}

    public function reviseSubmission(User $user, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        return DB::transaction(function () use ($user, $submission, $data) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureRevisionOwner($user, $locked);
            $locked->update([
                'cooperative_id' => $data['cooperative_id'],
                'submission_request_category_id' => $data['submission_request_category_id'],
                'submission_request_type_id' => $data['submission_request_type_id'],
                'recipient_bank_account_id' => $data['recipient_bank_account_id'],
                'submitter_city_id' => $user->city_id,
                'title' => $data['title'] ?? $this->generatedTitle($data),
                'purpose' => $data['purpose'] ?? $this->generatedPurpose($data),
                'needed_date' => $data['needed_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $total = $this->items->replaceItems($locked, $this->itemsFromSimplePayload($data));
            $locked->update(['total_amount' => $total]);
            $this->auditLog->record('submission.revision_updated', 'Revisi pengajuan disimpan.', $locked, [], ['submission_id' => $locked->id, 'revision_count' => $locked->revision_count]);

            return $locked->refresh();
        });
    }

    public function resubmit(User $user, FinancialSubmission $submission, ?string $message = null): FinancialSubmission
    {
        return DB::transaction(function () use ($user, $submission, $message) {
            $locked = FinancialSubmission::query()->with(['items', 'cooperative', 'submitter'])->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureRevisionOwner($user, $locked);
            if (! $user->assignedCooperatives()->whereKey($locked->cooperative_id)->exists()) {
                throw ValidationException::withMessages(['cooperative_id' => 'Assignment koperasi sudah tidak aktif.']);
            }
            if ($locked->items->isEmpty() || (float) $locked->total_amount <= 0) {
                throw ValidationException::withMessages(['submission' => 'Pengajuan harus memiliki item dengan total lebih dari 0.']);
            }
            if ($locked->items->count() !== 1) {
                throw ValidationException::withMessages(['submission' => 'Pengajuan hanya boleh memiliki satu nominal pengajuan.']);
            }

            $revision = $locked->openRevisionRequest()->lockForUpdate()->first();
            if (! $revision) {
                throw ValidationException::withMessages(['revision' => 'Tidak ada permintaan revisi aktif.']);
            }

            $response = $revision->response()->create([
                'financial_submission_id' => $locked->id,
                'responded_by' => $user->id,
                'message' => $message,
                'change_summary' => ['fields' => $revision->fields],
                'responded_at' => now(),
            ]);

            $revision->update(['status' => RevisionRequestStatus::RESPONDED, 'responded_at' => now()]);
            $locked->forceFill(['last_resubmitted_at' => now()])->save();
            $this->statuses->transition($locked, SubmissionStatus::SUBMITTED, $user, 'resubmitted', $message, [
                'revision_number' => $revision->revision_number,
                'response_id' => $response->id,
            ]);

            DB::afterCommit(function () use ($locked) {
                User::role('finance_staff')
                    ->where('is_active', true)
                    ->get()
                    ->each(fn (User $staff) => $staff->notify(new SubmissionResubmittedNotification($locked->fresh(['cooperative', 'submitter']))));
            });

            return $locked->refresh();
        });
    }

    private function ensureRevisionOwner(User $user, FinancialSubmission $submission): void
    {
        if ($submission->status !== SubmissionStatus::REVISION_REQUESTED || ! $submission->isOwnedBy($user)) {
            throw ValidationException::withMessages(['submission' => 'Pengajuan tidak dapat direvisi.']);
        }
    }

    private function itemsFromSimplePayload(array $data): array
    {
        $category = SubmissionCategory::where('code', 'other')->first() ?? SubmissionCategory::firstOrFail();
        $type = SubmissionRequestType::find($data['submission_request_type_id']);

        return [[
            'category_id' => $category->id,
            'description' => $type?->name ?? 'Pengajuan dana',
            'quantity' => 1,
            'unit' => 'pengajuan',
            'unit_price' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]];
    }

    private function generatedTitle(array $data): string
    {
        $category = SubmissionRequestCategory::find($data['submission_request_category_id']);
        $type = SubmissionRequestType::find($data['submission_request_type_id']);

        return trim(($category?->name ?? 'Pengajuan Dana').' - '.($type?->name ?? 'Umum'));
    }

    private function generatedPurpose(array $data): string
    {
        $type = SubmissionRequestType::find($data['submission_request_type_id']);

        return 'Pengajuan dana untuk '.($type?->name ?? 'kebutuhan operasional').'.';
    }
}
