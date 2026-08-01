<?php

namespace App\Enums;

enum DisbursementAttachmentType: string
{
    case TRANSFER_PROOF = 'transfer_proof';
    case PAYMENT_RECEIPT = 'payment_receipt';
    case OTHER = 'other';
}
