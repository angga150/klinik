<?php

namespace App\Models;

class DoctorSchedule extends ClinicModel
{
    protected $table = 'doctor_schedules';

    public function staff()
    {
        return $this->belongsTo(MedicalStaff::class, 'medical_staff_id');
    }

    public function polyclinic()
    {
        return $this->belongsTo(Polyclinic::class);
    }
}
