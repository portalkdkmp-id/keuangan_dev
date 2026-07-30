<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialSubmission extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'submission_number', 'type', 'status', 'cooperative_id', 'submitted_by', 'current_assignee_role',
        'submitter_city_id', 'submission_request_category_id', 'submission_request_type_id', 'recipient_bank_account_id',
        'title', 'purpose', 'needed_date', 'notes', 'total_amount', 'submitted_at',
        'finance_review_started_at', 'finance_reviewed_by', 'finance_validated_by', 'finance_validated_at',
        'forwarded_to_approval_by', 'forwarded_to_approval_at', 'revision_count',
        'last_revision_requested_at', 'last_resubmitted_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => SubmissionType::class,
            'status' => SubmissionStatus::class,
            'needed_date' => 'date',
            'submitted_at' => 'datetime',
            'finance_review_started_at' => 'datetime',
            'finance_validated_at' => 'datetime',
            'forwarded_to_approval_at' => 'datetime',
            'last_revision_requested_at' => 'datetime',
            'last_resubmitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function submitterCity()
    {
        return $this->belongsTo(City::class, 'submitter_city_id');
    }

    public function requestCategory()
    {
        return $this->belongsTo(SubmissionRequestCategory::class, 'submission_request_category_id');
    }

    public function requestType()
    {
        return $this->belongsTo(SubmissionRequestType::class, 'submission_request_type_id');
    }

    public function recipientBankAccount()
    {
        return $this->belongsTo(UserBankAccount::class, 'recipient_bank_account_id');
    }

    public function items()
    {
        return $this->hasMany(SubmissionItem::class)->orderBy('sort_order');
    }

    public function attachments()
    {
        return $this->hasMany(SubmissionAttachment::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(SubmissionStatusHistory::class)->latest('created_at');
    }

    public function financeDetail()
    {
        return $this->hasOne(FinanceSubmissionDetail::class);
    }

    public function revisionRequests()
    {
        return $this->hasMany(SubmissionRevisionRequest::class);
    }

    public function openRevisionRequest()
    {
        return $this->hasOne(SubmissionRevisionRequest::class)->where('status', 'open');
    }

    public function financeReviewer()
    {
        return $this->belongsTo(User::class, 'finance_reviewed_by');
    }

    public function financeValidator()
    {
        return $this->belongsTo(User::class, 'finance_validated_by');
    }

    public function approvalForwarder()
    {
        return $this->belongsTo(User::class, 'forwarded_to_approval_by');
    }

    public function isDraft(): bool
    {
        return $this->status === SubmissionStatus::DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === SubmissionStatus::SUBMITTED;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->submitted_by === $user->id;
    }

    public function canBeEditedBy(User $user): bool
    {
        return in_array($this->status, [SubmissionStatus::DRAFT, SubmissionStatus::REVISION_REQUESTED], true) && $this->isOwnedBy($user);
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->isDraft() && $this->isOwnedBy($user);
    }

    public function canBeSubmittedBy(User $user): bool
    {
        return $this->isDraft() && $this->isOwnedBy($user);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('submitted_by', $user->id);
    }

    public function scopeFinanceQueue(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SubmissionStatus::SUBMITTED->value,
            SubmissionStatus::FINANCE_REVIEW->value,
            SubmissionStatus::REVISION_REQUESTED->value,
            SubmissionStatus::FINANCE_VALIDATED->value,
            SubmissionStatus::APPROVAL_REVIEW->value,
        ]);
    }
}
