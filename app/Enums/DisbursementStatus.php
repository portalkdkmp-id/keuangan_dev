<?php

namespace App\Enums;

enum DisbursementStatus: string
{
    case COMPLETED = 'completed';
    case VOIDED = 'voided';
}
