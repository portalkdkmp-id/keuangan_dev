<?php

namespace App\Services\Reimbursement;

use App\Enums\AccountabilityStatus;
use App\Enums\ReimbursementAttachmentType;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Notifications\NewFinancialSubmissionNotification;
use App\Services\Audit\AuditLogService;
use App\Services\DocumentNumber\DocumentNumberService;
use App\Services\Submission\SubmissionStatusService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReimbursementService
{
    public function __construct(private DocumentNumberService $numbers, private ReimbursementCalculator $calculator, private ReimbursementValidationService $validation, private SubmissionStatusService $statuses, private AuditLogService $audit) {}

    public function createDraft(User $actor, array $data, array $purchase = [], array $payments = []): FinancialSubmission
    {
        $data['cooperative_id'] ??= null;

        return DB::transaction(function () use ($actor, $data, $purchase, $payments) {
            $this->guardReferences($actor, $data);
            $category = SubmissionRequestCategory::where('code', 'reimbursement')->firstOrFail();
            $account = $actor->bankAccounts()->where('is_active', true)->findOrFail($data['claimant_bank_account_id']);
            $total = $this->calculator->total($data['expenses']);
            $submission = FinancialSubmission::create(['submission_number' => $this->numbers->generateReimbursementNumber(), 'type' => SubmissionType::REIMBURSEMENT, 'status' => SubmissionStatus::DRAFT, 'submission_request_category_id' => $category->id, 'submission_request_type_id' => $data['expenses'][0]['expense_type_id'], 'cooperative_id' => $data['cooperative_id'], 'recipient_bank_account_id' => $account->id, 'bank_name_snapshot' => $account->bank_name, 'bank_account_number_snapshot' => $account->account_number, 'bank_account_holder_snapshot' => $account->account_holder_name, 'submitted_by' => $actor->id, 'submitter_city_id' => $actor->city_id, 'title' => $data['title'], 'purpose' => $data['summary'] ?? $data['title'], 'notes' => $data['summary'] ?? null, 'total_amount' => $total]);
            $submission->items()->create(['category_name' => 'Reimbursement', 'description' => $data['title'], 'quantity' => 1, 'unit' => 'klaim', 'unit_price' => $total, 'subtotal' => $total, 'sort_order' => 0]);
            $detail = $submission->reimbursementDetail()->create(['claimant_user_id' => $actor->id, 'claimant_bank_account_id' => $account->id, 'claimant_bank_name_snapshot' => $account->bank_name, 'claimant_account_number_snapshot' => $account->account_number, 'claimant_account_holder_snapshot' => $account->account_holder_name, 'expense_total' => $total, 'claimed_amount' => $total, 'expense_date_from' => collect($data['expenses'])->min('expense_date'), 'expense_date_to' => collect($data['expenses'])->max('expense_date'), 'summary' => $data['summary'] ?? null, 'source_type' => 'manual']);
            $this->replaceExpenses($actor, $detail, $data['expenses'], $purchase, $payments);
            $submission->statusHistories()->create(['to_status' => 'draft', 'changed_by' => $actor->id, 'action' => 'reimbursement_draft_created', 'created_at' => now()]);
            $this->audit->record('reimbursement.draft_created', 'Draft reimbursement dibuat.', $submission, [], ['claimed_amount' => $total]);

            return $submission;
        });
    }

    public function updateDraft(User $actor, FinancialSubmission $submission, array $data, array $purchase = [], array $payments = []): FinancialSubmission
    {
        $data['cooperative_id'] ??= null;

        return DB::transaction(function () use ($actor, $submission, $data, $purchase, $payments) {
            $locked = FinancialSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if (! $locked->canBeEditedBy($actor) || ! $locked->isReimbursement()) {
                throw ValidationException::withMessages(['reimbursement' => 'Reimbursement tidak dapat diubah.']);
            }$this->guardReferences($actor, $data);
            $account = $actor->bankAccounts()->where('is_active', true)->findOrFail($data['claimant_bank_account_id']);
            $total = $this->calculator->total($data['expenses']);
            $locked->update(['cooperative_id' => $data['cooperative_id'], 'recipient_bank_account_id' => $account->id, 'submission_request_type_id' => $data['expenses'][0]['expense_type_id'], 'title' => $data['title'], 'purpose' => $data['summary'] ?? $data['title'], 'notes' => $data['summary'] ?? null, 'total_amount' => $total, 'bank_name_snapshot' => $account->bank_name, 'bank_account_number_snapshot' => $account->account_number, 'bank_account_holder_snapshot' => $account->account_holder_name]);
            $locked->items()->update(['description' => $data['title'], 'unit_price' => $total, 'subtotal' => $total]);
            $detail = $locked->reimbursementDetail;
            $detail->update(['claimant_bank_account_id' => $account->id, 'claimant_bank_name_snapshot' => $account->bank_name, 'claimant_account_number_snapshot' => $account->account_number, 'claimant_account_holder_snapshot' => $account->account_holder_name, 'expense_total' => $total, 'claimed_amount' => $total, 'summary' => $data['summary'] ?? null]);
            $this->replaceExpenses($actor, $detail, $data['expenses'], $purchase, $payments);
            $this->audit->record('reimbursement.updated', 'Draft reimbursement diperbarui.', $locked, [], ['claimed_amount' => $total]);

            return $locked;
        });
    }

    public function submit(User $actor, FinancialSubmission $submission): FinancialSubmission
    {
        return DB::transaction(function () use ($actor, $submission) {
            $locked = FinancialSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if (! $locked->canBeSubmittedBy($actor)) {
                throw ValidationException::withMessages(['reimbursement' => 'Reimbursement tidak dapat diajukan.']);
            }$this->validation->ensureSubmittable($locked);

            $result = $this->statuses->transition($locked, SubmissionStatus::SUBMITTED, $actor, 'reimbursement_submitted');
            DB::afterCommit(fn () => User::role('finance_staff')->where('is_active', true)->get()->each(fn (User $staff) => $staff->notify(new NewFinancialSubmissionNotification($locked->fresh(['cooperative']), $actor))));

            return $result;
        });
    }

    public function createFromAccountabilityShortfall(User $actor, FundAccountabilityReport $report): FinancialSubmission
    {
        if ($report->submitted_by !== $actor->id || $report->status !== AccountabilityStatus::REIMBURSEMENT_PENDING || (float) $report->additional_amount <= 0) {
            throw ValidationException::withMessages(['report' => 'Laporan tidak memenuhi syarat reimbursement selisih.']);
        }
        if ($report->generatedReimbursement()->exists()) {
            throw ValidationException::withMessages(['report' => 'Reimbursement selisih sudah pernah dibuat.']);
        }

        $report->load('submission', 'items');
        $account = $actor->bankAccounts()->where('is_active', true)->orderByDesc('is_primary')->first();
        if (! $account) {
            throw ValidationException::withMessages(['account' => 'Tambahkan rekening aktif sebelum membuat reimbursement.']);
        }
        $fallbackType = SubmissionRequestType::where('is_active', true)->firstOrFail();
        $expenses = $report->items->map(fn ($item) => [
            'expense_date' => $item->expense_date->format('Y-m-d'), 'expense_type_id' => $item->category_id ?? $fallbackType->id,
            'vendor_name' => $item->vendor_name ?: 'Pengeluaran pertanggungjawaban', 'description' => $item->description,
            'actual_amount' => $item->amount, 'payment_method' => 'bank_transfer', 'payment_reference' => $item->invoice_number, 'notes' => $item->notes,
        ])->all();
        $submission = $this->createDraft($actor, [
            'title' => 'Reimbursement Selisih '.$report->report_number, 'cooperative_id' => $report->submission->cooperative_id,
            'claimant_bank_account_id' => $account->id, 'summary' => 'Kekurangan dana dari '.$report->report_number, 'expenses' => $expenses,
        ]);
        $amount = $report->additional_amount;
        $submission->update(['total_amount' => $amount]);
        $submission->items()->update(['unit_price' => $amount, 'subtotal' => $amount]);
        $submission->reimbursementDetail->update(['claimed_amount' => $amount, 'source_type' => $report->source_type === 'advance' ? 'advance_shortfall' : 'accountability_shortfall', 'source_accountability_report_id' => $report->id, 'source_advance_detail_id' => $report->advance_detail_id, 'source_additional_amount' => $amount]);
        $this->audit->record('reimbursement.generated_from_accountability', 'Draft reimbursement selisih dibuat.', $submission, [], ['accountability_report_id' => $report->id, 'claimed_amount' => $amount]);

        return $submission;
    }

    private function guardReferences(User $actor, array $data): void
    {
        if (! $actor->hasAnyRole(['super_admin', 'finance_staff']) && ! $actor->assignedCooperatives()->whereKey($data['cooperative_id'] ?? null)->exists()) {
            throw ValidationException::withMessages(['cooperative_id' => 'Koperasi tidak terhubung dengan user.']);
        }if (! $actor->bankAccounts()->where('is_active', true)->whereKey($data['claimant_bank_account_id'])->exists()) {
            throw ValidationException::withMessages(['claimant_bank_account_id' => 'Rekening harus aktif dan milik claimant.']);
        }
    }

    private function replaceExpenses(User $actor, $detail, array $expenses, array $purchase, array $payments): void
    {
        $existing = $detail->expenses()->with('attachments')->get()->values();
        $detail->expenses()->delete();
        foreach ($expenses as $i => $row) {
            $type = SubmissionRequestType::findOrFail($row['expense_type_id']);
            $expense = $detail->expenses()->create([...$row, 'expense_type_name_snapshot' => $type->name, 'sort_order' => $i]);
            foreach (['purchase_proof' => $purchase[$i] ?? [], 'payment_proof' => $payments[$i] ?? []] as $kind => $files) {
                $oldAttachments = $existing->get($i)?->attachments->filter(fn ($attachment) => $attachment->attachment_type->value === $kind) ?? collect();
                if (count($files) === 0) {
                    $oldAttachments->each(fn ($attachment) => $expense->attachments()->create($attachment->only(['uploaded_by', 'attachment_type', 'original_name', 'stored_name', 'disk', 'path', 'mime_type', 'extension', 'size'])));
                } else {
                    $oldAttachments->each(fn ($attachment) => Storage::disk($attachment->disk)->delete($attachment->path));
                }
                foreach ($files as $file) {
                    $this->store($actor, $expense, $file, $kind);
                }
            }
        }
        $existing->slice(count($expenses))->each(fn ($oldExpense) => $oldExpense->attachments->each(fn ($attachment) => Storage::disk($attachment->disk)->delete($attachment->path)));
    }

    private function store(User $actor, $expense, UploadedFile $file, string $kind): void
    {
        $path = $file->store('reimbursements/'.$expense->id, 'local');
        $expense->attachments()->create(['uploaded_by' => $actor->id, 'attachment_type' => ReimbursementAttachmentType::from($kind), 'original_name' => $file->getClientOriginalName(), 'stored_name' => basename($path), 'disk' => 'local', 'path' => $path, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'extension' => $file->getClientOriginalExtension(), 'size' => $file->getSize()]);
    }
}
