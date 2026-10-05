<?php

namespace App\Models;

class Medicine extends ClinicModel
{
    protected $table = 'medicines';

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function category()
    {
        return $this->belongsTo(MedicineCategory::class, 'medicine_category_id');
    }

    public function batches()
    {
        return $this->hasMany(MedicineBatch::class);
    }
}
