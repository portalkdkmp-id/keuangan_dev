<?php

namespace App\Enums;

enum FundReturnAttachmentType: string
{
    case TRANSFER_PROOF = 'transfer_proof';
    case HANDOVER_RECEIPT = 'handover_receipt';
    case SUPPORTING_DOCUMENT = 'supporting_document';
    case OTHER = 'other';
}
