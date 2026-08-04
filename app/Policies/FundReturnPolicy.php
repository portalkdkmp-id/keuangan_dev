<?php

namespace App\Policies;

use App\Enums\FundReturnStatus;
use App\Models\FundAccountabilityReport;
use App\Models\FundReturn;
use App\Models\User;

class FundReturnPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->can('fund-returns.view');
    }

    public function view(User $u, FundReturn $r): bool
    {
        return $u->hasRole('super_admin') || $u->can('fund-returns.review') || $u->can('fund-returns.approve') || ($u->can('fund-returns.view') && $r->returned_by === $u->id);
    }

    public function create(User $u, FundAccountabilityReport $r): bool
    {
        return $u->can('fund-returns.create') && $r->submitted_by === $u->id;
    }

    public function update(User $u, FundReturn $r): bool
    {
        return $u->can('fund-returns.update') && $r->returned_by === $u->id && in_array($r->status, [FundReturnStatus::DRAFT, FundReturnStatus::REVISION_REQUESTED], true);
    }

    public function submit(User $u, FundReturn $r): bool
    {
        return $u->can('fund-returns.submit') && $this->update($u, $r);
    }

    public function review(User $u, FundReturn $r): bool
    {
        return $u->can('fund-returns.review') && in_array($r->status, [FundReturnStatus::SUBMITTED, FundReturnStatus::FINANCE_REVIEW], true);
    }

    public function verify(User $u, FundReturn $r): bool
    {
        return $u->can('fund-returns.verify') && $r->status === FundReturnStatus::FINANCE_REVIEW;
    }

    public function approve(User $u, FundReturn $r): bool
    {
        return $u->can('fund-returns.approve') && $r->status === FundReturnStatus::FINANCE_VERIFIED;
    }

    public function downloadAttachment(User $u, FundReturn $r): bool
    {
        return $u->can('fund-returns.download-attachment') && $this->view($u, $r);
    }
}
