<?php

namespace App\Enums;

enum VisitStatus: string
{
    case WaitingTriage = 'waiting_triage';
    case WaitingDoctor = 'waiting_doctor';
    case InConsultation = 'in_consultation';
    case WaitingPharmacy = 'waiting_pharmacy';
    case WaitingPayment = 'waiting_payment';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}
