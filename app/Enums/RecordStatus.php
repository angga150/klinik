<?php

namespace App\Enums;

enum RecordStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Amended = 'amended';
}
