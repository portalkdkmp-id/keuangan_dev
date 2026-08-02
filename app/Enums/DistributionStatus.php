<?php

namespace App\Enums;

enum DistributionStatus: string
{
    case NOT_REQUIRED = 'not_required';
    case PENDING = 'pending';
    case PARTIALLY_DISTRIBUTED = 'partially_distributed';
    case FULLY_DISTRIBUTED = 'fully_distributed';
    case RECIPIENT_CONFIRMED = 'recipient_confirmed';
    case ACCOUNTABILITY_PENDING = 'accountability_pending';
    case ACCOUNTABILITY_SUBMITTED = 'accountability_submitted';
    case UNDER_VERIFICATION = 'under_verification';
    case ACCOUNTABILITY_APPROVED = 'accountability_approved';
    case CLOSED = 'closed';
}
