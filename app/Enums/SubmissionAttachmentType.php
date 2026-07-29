<?php

namespace App\Enums;

enum SubmissionAttachmentType: string
{
    case SUPPORTING_DOCUMENT = 'supporting_document';
    case QUOTATION = 'quotation';
    case INVOICE = 'invoice';
    case PHOTO = 'photo';
    case OTHER = 'other';
}
