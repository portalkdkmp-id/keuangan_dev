<?php

namespace App\Enums;

enum AdvanceStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case PENDING_DISBURSEMENT = 'pending_disbursement';
    case DISBURSED = 'disbursed';
    case SETTLEMENT_DUE = 'settlement_due';
    case SETTLEMENT_DRAFT = 'settlement_draft';
    case SETTLEMENT_SUBMITTED = 'settlement_submitted';
    case SETTLEMENT_REVISION_REQUESTED = 'settlement_revision_requested';
    case SETTLEMENT_VERIFIED = 'settlement_verified';
    case RETURN_PENDING = 'return_pending';
    case REIMBURSEMENT_PENDING = 'reimbursement_pending';
    case CLOSED = 'closed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
