<?php

namespace App\Models;

class Visit extends ClinicModel
{
    protected $table = 'visits';

    protected function casts(): array
    {
        return ['visit_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function polyclinic()
    {
        return $this->belongsTo(Polyclinic::class);
    }

    public function doctor()
    {
        return $this->belongsTo(MedicalStaff::class, 'medical_staff_id');
    }

    public function queue()
    {
        return $this->hasOne(QueueEntry::class, 'visit_id');
    }

    public function vitals()
    {
        return $this->hasOne(VitalSign::class, 'visit_id');
    }

    public function record()
    {
        return $this->hasOne(MedicalRecord::class, 'visit_id');
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class, 'visit_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
