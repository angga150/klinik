<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case Triage = 'triage';
    case InConsultation = 'in_consultation';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}
