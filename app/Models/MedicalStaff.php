<?php

namespace App\Models;

class MedicalStaff extends ClinicModel
{
    protected $table = 'medical_staff';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
