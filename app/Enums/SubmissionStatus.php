<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case FINANCE_REVIEW = 'finance_review';
    case REVISION_REQUESTED = 'revision_requested';
    case FINANCE_VALIDATED = 'finance_validated';
    case APPROVAL_REVIEW = 'approval_review';
    case APPROVAL_IN_REVIEW = 'approval_in_review';
    case APPROVAL_REVISION_REQUESTED = 'approval_revision_requested';
    case APPROVAL_REJECTED = 'approval_rejected';
    case DIRECTOR_REVIEW = 'director_review';
    case CANCELLED = 'cancelled';
}
