<?php

namespace App\Models;

class StockOpname extends ClinicModel
{
    protected $table = 'stock_opnames';

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
