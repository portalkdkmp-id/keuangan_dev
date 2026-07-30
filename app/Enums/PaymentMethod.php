<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case VIRTUAL_ACCOUNT = 'virtual_account';
    case OTHER = 'other';
}
