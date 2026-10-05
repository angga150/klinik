<?php

namespace App\Enums;

enum PrescriptionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Processing = 'processing';
    case Ready = 'ready';
    case Dispensed = 'dispensed';
    case Cancelled = 'cancelled';
}
