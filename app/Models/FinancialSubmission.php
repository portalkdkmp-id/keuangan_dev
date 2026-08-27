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
        'title', 'purpose', 'needed_date', 'notes', 'total_amount', 'is_urgent', 'submitted_at',
        'finance_review_started_at', 'finance_reviewed_by', 'finance_validated_by', 'finance_validated_at',
        'forwarded_to_approval_by', 'forwarded_to_approval_at', 'revision_count',
        'last_revision_requested_at', 'last_resubmitted_at', 'cancelled_at',
        'approval_reviewed_by', 'approval_review_started_at', 'approval_decided_by',
        'approval_decided_at', 'approval_approved_amount', 'approval_revision_count',
        'last_approval_revision_requested_at', 'last_approval_resubmitted_at',
        'forwarded_to_director_by', 'forwarded_to_director_at',
        'bank_name_snapshot', 'bank_account_number_snapshot', 'bank_account_holder_snapshot',
        'director_reviewed_by', 'director_review_started_at', 'director_decided_by',
        'director_decided_at', 'director_approved_amount', 'director_revision_count',
        'last_director_revision_requested_at', 'last_director_resubmitted_at',
        'disbursement_status', 'disbursed_at', 'disbursed_amount', 'disbursed_by',
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
            'is_urgent' => 'boolean',
            'approval_review_started_at' => 'datetime',
            'approval_decided_at' => 'datetime',
            'approval_approved_amount' => 'decimal:2',
            'last_approval_revision_requested_at' => 'datetime',
            'last_approval_resubmitted_at' => 'datetime',
            'forwarded_to_director_at' => 'datetime',
            'director_review_started_at' => 'datetime',
            'director_decided_at' => 'datetime',
            'director_approved_amount' => 'decimal:2',
            'last_director_revision_requested_at' => 'datetime',
            'last_director_resubmitted_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'disbursed_amount' => 'decimal:2',
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

    public function approvalReviewer()
    {
        return $this->belongsTo(User::class, 'approval_reviewed_by');
    }

    public function approvalDecisionMaker()
    {
        return $this->belongsTo(User::class, 'approval_decided_by');
    }

    public function directorForwarder()
    {
        return $this->belongsTo(User::class, 'forwarded_to_director_by');
    }

    public function directorReviewer()
    {
        return $this->belongsTo(User::class, 'director_reviewed_by');
    }

    public function directorDecisionMaker()
    {
        return $this->belongsTo(User::class, 'director_decided_by');
    }

    public function disburser()
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function approvalReviews()
    {
        return $this->hasMany(SubmissionApprovalReview::class)->orderByDesc('review_number');
    }

    public function latestApprovalReview()
    {
        return $this->hasOne(SubmissionApprovalReview::class)->orderByDesc('review_number');
    }

    public function activeApprovalReview()
    {
        return $this->hasOne(SubmissionApprovalReview::class)->whereIn('status', ['pending', 'in_review', 'revision_requested'])->orderByDesc('review_number');
    }

    public function directorReviews()
    {
        return $this->hasMany(SubmissionDirectorReview::class)->orderByDesc('review_number');
    }

    public function activeDirectorReview()
    {
        return $this->hasOne(SubmissionDirectorReview::class)->whereIn('status', ['pending', 'in_review', 'revision_requested'])->orderByDesc('review_number');
    }

    public function disbursement()
    {
        return $this->hasOne(SubmissionDisbursement::class);
    }

    public function fundDistributions()
    {
        return $this->hasMany(FundDistribution::class);
    }

    public function receiptConfirmations()
    {
        return $this->hasMany(FundReceiptConfirmation::class);
    }

    public function accountabilityReport()
    {
        return $this->hasOne(FundAccountabilityReport::class);
    }

    public function reimbursementDetail()
    {
        return $this->hasOne(ReimbursementDetail::class);
    }

    public function advanceDetail()
    {
        return $this->hasOne(AdvanceDetail::class);
    }

    public function isAdvance(): bool
    {
        return $this->type === SubmissionType::ADVANCE && $this->advanceDetail()->exists();
    }

    public function isReimbursement(): bool
    {
        if ($this->type === SubmissionType::REIMBURSEMENT) {
            return true;
        }

        return $this->relationLoaded('requestCategory') ? $this->requestCategory?->code === 'reimbursement' : $this->requestCategory()->where('code', 'reimbursement')->exists();
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
            SubmissionStatus::APPROVAL_REVISION_REQUESTED->value,
            SubmissionStatus::APPROVAL_REJECTED->value,
            SubmissionStatus::DIRECTOR_REVIEW->value,
        ]);
    }
}
