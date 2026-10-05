<?php

namespace App\Models;

class Patient extends ClinicModel
{
    protected $table = 'patients';

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
