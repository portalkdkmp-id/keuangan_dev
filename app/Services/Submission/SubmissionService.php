<?php

namespace App\Services\Submission;

use App\Enums\RevisionRequestStatus;
use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\FinancialSubmission;
use App\Models\SubmissionCategory;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use App\Models\User;
use App\Notifications\NewFinancialSubmissionNotification;
use App\Services\Audit\AuditLogService;
use App\Services\DocumentNumber\DocumentNumberService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly SubmissionItemService $items,
        private readonly SubmissionStatusService $statuses,
        private readonly AuditLogService $auditLog,
    ) {}

    public function paginateForPic(User $user, array $filters): LengthAwarePaginator
    {
        return FinancialSubmission::query()
            ->ownedBy($user)
            ->with(['cooperative:id,name'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('submission_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhereHas('cooperative', fn ($cooperative) => $cooperative->where('name', 'like', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['cooperative_id'] ?? null, fn ($query, $id) => $query->where('cooperative_id', $id))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function paginateFinanceQueue(array $filters): LengthAwarePaginator
    {
        return FinancialSubmission::query()
            ->financeQueue()
            ->with(['cooperative.city.province', 'submitter:id,name,email'])
            ->withCount('attachments')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('submission_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhereHas('cooperative', fn ($cooperative) => $cooperative->where('name', 'like', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByRaw("case when status = 'submitted' then 0 else 1 end")
            ->orderByRaw('needed_date asc nulls last')
            ->orderBy('submitted_at')
            ->paginate(10)
            ->withQueryString();
    }

    public function createDraft(User $user, array $data): FinancialSubmission
    {
        $this->ensureAssignedCooperative($user, $data['cooperative_id']);

        return DB::transaction(function () use ($user, $data) {
            $account = $user->bankAccounts()->whereKey($data['recipient_bank_account_id'])->first();
            $submission = FinancialSubmission::create([
                'submission_number' => $this->numbers->generateFundRequestNumber(),
                'type' => SubmissionType::FUND_REQUEST,
                'status' => SubmissionStatus::DRAFT,
                'submission_request_category_id' => $data['submission_request_category_id'],
                'submission_request_type_id' => $data['submission_request_type_id'],
                'cooperative_id' => $data['cooperative_id'],
                'recipient_bank_account_id' => $data['recipient_bank_account_id'],
                'bank_name_snapshot' => $account?->bank_name,
                'bank_account_number_snapshot' => $account?->account_number,
                'bank_account_holder_snapshot' => $account?->account_holder_name,
                'submitted_by' => $user->id,
                'submitter_city_id' => $user->city_id,
                'title' => $data['title'] ?? $this->generatedTitle($data),
                'purpose' => $data['purpose'] ?? $this->generatedPurpose($data),
                'needed_date' => $data['needed_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total_amount' => 0,
            ]);

            $total = $this->items->replaceItems($submission, $this->itemsFromSimplePayload($data));
            $submission->update(['total_amount' => $total]);
            $submission->statusHistories()->create(['to_status' => SubmissionStatus::DRAFT, 'changed_by' => $user->id, 'action' => 'created', 'created_at' => now()]);
            $this->auditLog->record('submission.draft_created', 'Draft pengajuan dibuat.', $submission, [], $this->auditPayload($submission));

            return $submission->refresh()->load(['items', 'cooperative']);
        });
    }

    public function updateDraft(User $user, FinancialSubmission $submission, array $data): FinancialSubmission
    {
        $this->ensureDraftOwner($user, $submission);
        $this->ensureAssignedCooperative($user, $data['cooperative_id']);

        return DB::transaction(function () use ($user, $submission, $data) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $old = $this->auditPayload($locked);
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
            $locked->statusHistories()->create(['from_status' => SubmissionStatus::DRAFT, 'to_status' => SubmissionStatus::DRAFT, 'changed_by' => $user->id, 'action' => 'updated', 'created_at' => now()]);
            $this->auditLog->record('submission.draft_updated', 'Draft pengajuan diperbarui.', $locked, $old, $this->auditPayload($locked));

            return $locked->refresh()->load(['items', 'cooperative']);
        });
    }

    public function deleteDraft(User $user, FinancialSubmission $submission): void
    {
        $this->ensureDraftOwner($user, $submission);

        DB::transaction(function () use ($submission) {
            $old = $this->auditPayload($submission);
            $submission->delete();
            $this->auditLog->record('submission.draft_deleted', 'Draft pengajuan dihapus.', $submission, $old);
        });
    }

    public function submit(User $user, FinancialSubmission $submission): FinancialSubmission
    {
        return DB::transaction(function () use ($user, $submission) {
            $locked = FinancialSubmission::query()->with(['items', 'cooperative'])->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $this->ensureDraftOwner($user, $locked);
            $this->ensureAssignedCooperative($user, $locked->cooperative_id);

            if ($locked->items->isEmpty() || (float) $locked->total_amount <= 0) {
                throw ValidationException::withMessages(['submission' => 'Pengajuan harus memiliki item dengan total lebih dari 0.']);
            }
            if ($locked->items->count() !== 1) {
                throw ValidationException::withMessages(['submission' => 'Pengajuan hanya boleh memiliki satu nominal pengajuan.']);
            }

            $this->statuses->transition($locked, SubmissionStatus::SUBMITTED, $user, 'submitted');

            DB::afterCommit(function () use ($locked, $user) {
                User::role('finance_staff')
                    ->where('is_active', true)
                    ->get()
                    ->each(fn (User $staff) => $staff->notify(new NewFinancialSubmissionNotification($locked->fresh(['cooperative']), $user)));
            });

            return $locked->refresh();
        });
    }

    public function cancelDraft(User $user, FinancialSubmission $submission, ?string $reason = null): FinancialSubmission
    {
        return DB::transaction(function () use ($user, $submission, $reason) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isOwnedBy($user) || ! in_array($locked->status, [SubmissionStatus::DRAFT, SubmissionStatus::REVISION_REQUESTED], true)) {
                throw ValidationException::withMessages(['submission' => 'Pengajuan tidak dapat dibatalkan.']);
            }
            $locked->openRevisionRequest()->update(['status' => RevisionRequestStatus::CANCELLED, 'resolved_at' => now()]);

            return $this->statuses->transition($locked, SubmissionStatus::CANCELLED, $user, 'cancelled', $reason);
        });
    }

    public function startFinanceReview(User $user, FinancialSubmission $submission): FinancialSubmission
    {
        return DB::transaction(function () use ($user, $submission) {
            $locked = FinancialSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill(['finance_reviewed_by' => $user->id])->save();

            return $this->statuses->transition($locked, SubmissionStatus::FINANCE_REVIEW, $user, 'finance_review_started');
        });
    }

    private function ensureDraftOwner(User $user, FinancialSubmission $submission): void
    {
        if (! $submission->isDraft() || ! $submission->isOwnedBy($user)) {
            throw ValidationException::withMessages(['submission' => 'Draft tidak dapat diubah.']);
        }
    }

    private function ensureAssignedCooperative(User $user, string $cooperativeId): void
    {
        if (! $user->assignedCooperatives()->whereKey($cooperativeId)->exists()) {
            throw ValidationException::withMessages(['cooperative_id' => 'Koperasi tidak termasuk assignment Anda.']);
        }
    }

    private function auditPayload(FinancialSubmission $submission): array
    {
        return [
            'submission_id' => $submission->id,
            'submission_number' => $submission->submission_number,
            'cooperative_id' => $submission->cooperative_id,
            'status' => $submission->status->value,
            'total_amount' => $submission->total_amount,
        ];
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
