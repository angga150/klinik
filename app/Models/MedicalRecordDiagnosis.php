<?php

namespace App\Models;

class MedicalRecordDiagnosis extends ClinicModel
{
    protected $table = 'medical_record_diagnoses';

    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }
}
