<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case FINANCE_REVIEW = 'finance_review';
    case REVISION_REQUESTED = 'revision_requested';
    case CANCELLED = 'cancelled';
}
