<?php

namespace App\Enums;

enum SubmissionType: string
{
    case FUND_REQUEST = 'fund_request';
    case REIMBURSEMENT = 'reimbursement';
    case ADVANCE = 'advance';
}
