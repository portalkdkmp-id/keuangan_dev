<?php

namespace App\Enums;

enum DisbursementRecipientType: string
{
    case FINANCE_STAFF = 'finance_staff';
    case PIC_KDKMP = 'pic_kdkmp';
    case COOPERATIVE = 'cooperative';
    case OTHER = 'other';
}
