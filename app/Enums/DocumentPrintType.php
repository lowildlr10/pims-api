<?php

namespace App\Enums;

enum DocumentPrintType: string
{
    case PR = 'pr';
    case RFQ = 'rfq';
    case AOQ = 'aoq';
    case PO = 'po';
    case IAR = 'iar';
    case OBR = 'obr';
    case DV = 'dv';
    case RIS = 'ris';
    case ICS = 'ics';
    case ARE = 'are';
    case SUMMARY = 'summary';
    case PAYMENT = 'payment';
    case UNDEFINED = '';
}
