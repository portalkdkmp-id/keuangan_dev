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
        'title', 'purpose', 'needed_date', 'notes', 'total_amount', 'submitted_at',
        'finance_review_started_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => SubmissionType::class,
            'status' => SubmissionStatus::class,
            'needed_date' => 'date',
            'submitted_at' => 'datetime',
            'finance_review_started_at' => 'datetime',
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
        return $this->isDraft() && $this->isOwnedBy($user);
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
        return $query->whereIn('status', [SubmissionStatus::SUBMITTED->value, SubmissionStatus::FINANCE_REVIEW->value]);
    }
}
