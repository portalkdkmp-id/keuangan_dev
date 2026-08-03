<?php

namespace App\Enums;

enum AccountabilityStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case FINANCE_REVIEW = 'finance_review';
    case REVISION_REQUESTED = 'revision_requested';
    case FINANCE_VERIFIED = 'finance_verified';
    case APPROVED = 'approved';
    case RETURN_PENDING = 'return_pending';
    case REIMBURSEMENT_PENDING = 'reimbursement_pending';
    case CLOSED = 'closed';
}
