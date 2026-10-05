<?php

namespace App\Models;

class PrescriptionItem extends ClinicModel
{
    protected $table = 'prescription_items';

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function allocations()
    {
        return $this->hasMany(DispenseAllocation::class);
    }
}
