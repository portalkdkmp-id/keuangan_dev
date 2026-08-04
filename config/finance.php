<?php

return [
    'attachment_disk' => env('FINANCE_ATTACHMENT_DISK', 'local'),
    'advance' => ['supporting_document_threshold' => (int) env('ADVANCE_SUPPORTING_DOCUMENT_THRESHOLD', 0), 'default_settlement_days' => (int) env('ADVANCE_DEFAULT_SETTLEMENT_DAYS', 14), 'max_settlement_days' => (int) env('ADVANCE_MAX_SETTLEMENT_DAYS', 30)],
];
