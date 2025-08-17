<?php

namespace App\Enums;

enum ObligationRequestStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case DISAPPROVED = 'disapproved';
    case OBLIGATED = 'obligated';
}
