<?php

namespace App\Services\Advance;

use App\Enums\AdvanceStatus;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\FinancialSubmission;
use App\Models\SubmissionDisbursement;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Notifications\NewFinancialSubmissionNotification;
use App\Services\Audit\AuditLogService;
use App\Services\DocumentNumber\DocumentNumberService;
use App\Services\Submission\SubmissionStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdvanceService
{
    public function __construct(private DocumentNumberService $numbers, private SubmissionStatusService $statuses, private AdvanceValidationService $validation, private AuditLogService $audit) {}

    public function createDraft(User $actor, array $data): FinancialSubmission
    {
        $data['cooperative_id'] ??= null;

        return DB::transaction(function () use ($actor, $data) {
            $this->guardActor($actor);
            $account = $actor->bankAccounts()->where('is_active', true)->findOrFail($data['recipient_bank_account_id']);
            $category = SubmissionRequestCategory::where('code', 'advance')->firstOrFail();
            $type = SubmissionRequestType::where('is_active', true)->firstOrFail();
            $submission = FinancialSubmission::create(['submission_number' => $this->numbers->generateAdvanceNumber(), 'type' => SubmissionType::ADVANCE, 'status' => SubmissionStatus::DRAFT, 'submission_request_category_id' => $category->id, 'submission_request_type_id' => $type->id, 'cooperative_id' => $data['cooperative_id'], 'recipient_bank_account_id' => $account->id, 'bank_name_snapshot' => $account->bank_name, 'bank_account_number_snapshot' => $account->account_number, 'bank_account_holder_snapshot' => $account->account_holder_name, 'submitted_by' => $actor->id, 'submitter_city_id' => $actor->city_id, 'title' => $data['title'], 'purpose' => $data['purpose'], 'needed_date' => $data['expected_transaction_date'] ?? null, 'notes' => $data['notes'] ?? null, 'total_amount' => $data['estimated_amount']]);
            $submission->items()->create(['category_name' => 'Uang Panjar', 'description' => $data['purpose'], 'quantity' => 1, 'unit' => 'panjar', 'unit_price' => $data['estimated_amount'], 'subtotal' => $data['estimated_amount'], 'sort_order' => 0]);
            $submission->advanceDetail()->create(['requester_id' => $actor->id, 'responsible_user_id' => $actor->id, 'cooperative_id' => $data['cooperative_id'], 'purpose' => $data['purpose'], 'estimated_amount' => $data['estimated_amount'], 'expected_transaction_date' => $data['expected_transaction_date'] ?? null, 'expected_settlement_date' => $data['expected_settlement_date'], 'settlement_due_days' => now()->diffInDays($data['expected_settlement_date']), 'recipient_bank_account_id' => $account->id, 'recipient_bank_name_snapshot' => $account->bank_name, 'recipient_account_number_snapshot' => $account->account_number, 'recipient_account_holder_snapshot' => $account->account_holder_name, 'advance_status' => AdvanceStatus::DRAFT, 'notes' => $data['notes'] ?? null]);
            $submission->statusHistories()->create(['to_status' => 'draft', 'changed_by' => $actor->id, 'action' => 'advance_draft_created', 'created_at' => now()]);
            $this->audit->record('advance.draft_created', 'Draft uang panjar dibuat.', $submission, [], ['estimated_amount' => $data['estimated_amount'], 'expected_settlement_date' => $data['expected_settlement_date']]);

            return $submission;
        });
    }

    public function updateDraft(User $actor, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        $data['cooperative_id'] ??= null;

        return DB::transaction(function () use ($actor, $submission, $data) {
            $locked = FinancialSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isAdvance() || ! $locked->canBeEditedBy($actor)) {
                throw ValidationException::withMessages(['advance' => 'Panjar tidak dapat diubah.']);
            }$account = $actor->bankAccounts()->where('is_active', true)->findOrFail($data['recipient_bank_account_id']);
            $locked->update(['cooperative_id' => $data['cooperative_id'], 'recipient_bank_account_id' => $account->id, 'title' => $data['title'], 'purpose' => $data['purpose'], 'needed_date' => $data['expected_transaction_date'] ?? null, 'notes' => $data['notes'] ?? null, 'total_amount' => $data['estimated_amount'], 'bank_name_snapshot' => $account->bank_name, 'bank_account_number_snapshot' => $account->account_number, 'bank_account_holder_snapshot' => $account->account_holder_name]);
            $locked->items()->update(['description' => $data['purpose'], 'unit_price' => $data['estimated_amount'], 'subtotal' => $data['estimated_amount']]);
            $locked->advanceDetail->update(['cooperative_id' => $data['cooperative_id'], 'purpose' => $data['purpose'], 'estimated_amount' => $data['estimated_amount'], 'expected_transaction_date' => $data['expected_transaction_date'] ?? null, 'expected_settlement_date' => $data['expected_settlement_date'], 'recipient_bank_account_id' => $account->id, 'recipient_bank_name_snapshot' => $account->bank_name, 'recipient_account_number_snapshot' => $account->account_number, 'recipient_account_holder_snapshot' => $account->account_holder_name, 'notes' => $data['notes'] ?? null]);
            $this->audit->record('advance.updated', 'Draft uang panjar diperbarui.', $locked);

            return $locked;
        });
    }

    public function submit(User $actor, FinancialSubmission $submission): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission) {
            $locked = FinancialSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->validation->ensureSubmittable($actor, $locked);
            $result = $this->statuses->transition($locked, SubmissionStatus::SUBMITTED, $actor, 'advance_submitted');
            DB::afterCommit(fn () => User::role('finance_staff')->where('is_active', true)->whereKeyNot($actor->id)->get()->each(fn (User $staff) => $staff->notify(new NewFinancialSubmissionNotification($locked->fresh(['cooperative']), $actor))));

            return $result;
        });
    }

    public function markDisbursed(FinancialSubmission $submission, SubmissionDisbursement $disbursement): void
    {
        if (! $submission->isAdvance()) {
            return;
        }
        $advance = $submission->advanceDetail;
        $advance->update(['advance_status' => AdvanceStatus::SETTLEMENT_DUE, 'approved_amount' => $submission->director_approved_amount, 'disbursed_amount' => $disbursement->amount, 'disbursed_at' => $disbursement->transferred_at]);
        $this->audit->record('advance.disbursed', 'Uang panjar dicairkan.', $submission, [], ['amount' => $disbursement->amount, 'settlement_due' => $advance->expected_settlement_date]);
    }

    private function guardActor(User $actor): void
    {
        if (! $actor->hasRole('finance_staff') && ! $actor->hasRole('super_admin')) {
            throw ValidationException::withMessages(['advance' => 'Hanya Finance Staff yang dapat membuat uang panjar.']);
        }
    }
}
