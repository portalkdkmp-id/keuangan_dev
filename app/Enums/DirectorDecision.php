<?php

namespace App\Enums;

enum DirectorDecision: string
{
    case APPROVED_PENDING_DISBURSEMENT = 'approved_pending_disbursement';
    case APPROVED_AND_DISBURSED = 'approved_and_disbursed';
    case REJECTED = 'rejected';
    case REVISION_REQUESTED = 'revision_requested';
}
