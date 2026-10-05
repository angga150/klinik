<?php

namespace App\Models;

class Prescription extends ClinicModel
{
    protected $table = 'prescriptions';

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}
