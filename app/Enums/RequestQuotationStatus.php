<?php

namespace App\Enums;

enum RequestQuotationStatus: string
{
    case DRAFT = 'draft';
    case CANVASSING = 'canvassing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
