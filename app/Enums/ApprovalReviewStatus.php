<?php

namespace App\Enums;

enum ApprovalReviewStatus: string
{
    case PENDING = 'pending';
    case IN_REVIEW = 'in_review';
    case REVISION_REQUESTED = 'revision_requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SUPERSEDED = 'superseded';
}
