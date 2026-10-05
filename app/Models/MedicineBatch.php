<?php

namespace App\Models;

class MedicineBatch extends ClinicModel
{
    protected $table = 'medicine_batches';

    protected function casts(): array
    {
        return ['expires_on' => 'date', 'purchase_price' => 'decimal:2'];
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
