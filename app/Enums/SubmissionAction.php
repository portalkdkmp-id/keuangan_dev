<?php

namespace App\Enums;

enum SubmissionAction: string
{
    case START_APPROVAL_REVIEW = 'approval_review_started';
    case APPROVE_BY_FINANCE_APPROVER = 'approved_by_finance_approver';
    case REJECT_BY_FINANCE_APPROVER = 'rejected_by_finance_approver';
    case REQUEST_APPROVAL_REVISION = 'approval_revision_requested';
    case UPDATE_APPROVAL_REVISION = 'approval_revision_updated';
    case RESUBMIT_TO_APPROVAL = 'resubmitted_to_approval';
    case FORWARD_TO_DIRECTOR = 'forwarded_to_finance_director';
}
