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
    case START_DIRECTOR_REVIEW = 'director_review_started';
    case APPROVE_PENDING_DISBURSEMENT = 'approved_pending_disbursement';
    case APPROVE_AND_DISBURSE = 'approved_and_disbursed';
    case DISBURSE_APPROVED_SUBMISSION = 'fund_disbursed';
    case REJECT_BY_DIRECTOR = 'rejected_by_finance_director';
    case REQUEST_DIRECTOR_REVISION = 'director_revision_requested';
    case RESUBMIT_TO_DIRECTOR = 'resubmitted_to_finance_director';
}
