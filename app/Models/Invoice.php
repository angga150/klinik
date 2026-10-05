<?php

namespace App\Models;

class Invoice extends ClinicModel
{
    protected $table = 'invoices';

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
