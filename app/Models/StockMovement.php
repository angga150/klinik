<?php

namespace App\Models;

class StockMovement extends ClinicModel
{
    protected $table = 'stock_movements';

    public function batch()
    {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
