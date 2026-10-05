<?php

namespace App\Models;

class MedicalRecordAmendment extends ClinicModel
{
    protected $table = 'medical_record_amendments';

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
