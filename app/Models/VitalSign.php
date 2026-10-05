<?php

namespace App\Models;

class VitalSign extends ClinicModel
{
    protected $table = 'vital_signs';

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
