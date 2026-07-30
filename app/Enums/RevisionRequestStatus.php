<?php

namespace App\Enums;

enum RevisionRequestStatus: string
{
    case OPEN = 'open';
    case RESPONDED = 'responded';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';
}
