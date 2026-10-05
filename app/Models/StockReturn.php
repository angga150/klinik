<?php

namespace App\Models;

class StockReturn extends ClinicModel
{
    protected $table = 'stock_returns';

    public function batch()
    {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }
}
