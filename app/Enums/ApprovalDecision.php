<?php

namespace App\Enums;

enum ApprovalDecision: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case REVISION_REQUESTED = 'revision_requested';
}
