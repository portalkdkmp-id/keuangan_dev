<?php

namespace App\Enums;

enum DirectorReviewStatus: string
{
    case PENDING = 'pending';
    case IN_REVIEW = 'in_review';
    case REVISION_REQUESTED = 'revision_requested';
    case APPROVED_PENDING_DISBURSEMENT = 'approved_pending_disbursement';
    case APPROVED_AND_DISBURSED = 'approved_and_disbursed';
    case REJECTED = 'rejected';
    case SUPERSEDED = 'superseded';
}
