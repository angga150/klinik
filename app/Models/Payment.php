<?php

namespace App\Models;

class Payment extends ClinicModel
{
    protected $table = 'payments';

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'amount' => 'decimal:2', 'received' => 'decimal:2', 'change' => 'decimal:2'];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function void()
    {
        return $this->hasOne(PaymentVoid::class);
    }
}
