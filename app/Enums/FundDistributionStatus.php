<?php

namespace App\Enums;

enum FundDistributionStatus: string
{
    case COMPLETED = 'completed';
    case RECIPIENT_CONFIRMED = 'recipient_confirmed';
    case CANCELLED = 'cancelled';
}
