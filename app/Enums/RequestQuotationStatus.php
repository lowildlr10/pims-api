<?php

namespace App\Enums;

enum RequestQuotation: string
{
    case DRAFT = 'draft';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
}
