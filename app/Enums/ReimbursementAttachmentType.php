<?php

namespace App\Enums;

enum ReimbursementAttachmentType: string
{
    case PURCHASE_PROOF = 'purchase_proof';
    case PAYMENT_PROOF = 'payment_proof';
    case SUPPORTING_DOCUMENT = 'supporting_document';
    case OTHER = 'other';
}
