<?php

namespace App\Enums;

enum FundReturnStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case FINANCE_REVIEW = 'finance_review';
    case REVISION_REQUESTED = 'revision_requested';
    case FINANCE_VERIFIED = 'finance_verified';
    case REJECTED = 'rejected';
    case CLOSED = 'closed';
}
