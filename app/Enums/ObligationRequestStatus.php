<?php

namespace App\Enums;

enum ObligationRequestStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DISAPPROVED = 'disapproved';
}
