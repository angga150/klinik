<?php

namespace App\Models;

class PaymentVoid extends ClinicModel
{
    protected $table = 'payment_voids';

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
