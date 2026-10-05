<?php

namespace App\Models;

class Purchase extends ClinicModel
{
    protected $table = 'purchases';

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
