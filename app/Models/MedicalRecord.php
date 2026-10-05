<?php

namespace App\Models;

class MedicalRecord extends ClinicModel
{
    protected $table = 'medical_records';

    protected function casts(): array
    {
        return ['control_date' => 'date', 'finalized_at' => 'datetime'];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function diagnoses()
    {
        return $this->hasMany(MedicalRecordDiagnosis::class, 'medical_record_id');
    }

    public function procedures()
    {
        return $this->hasMany(MedicalRecordProcedure::class, 'medical_record_id');
    }

    public function amendments()
    {
        return $this->hasMany(MedicalRecordAmendment::class, 'medical_record_id');
    }
}
