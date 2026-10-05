<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class ClinicModel extends Model
{
    protected $guarded = ['id'];

    public function scopeForClinic(Builder $query, int $clinicId): Builder
    {
        return $query->where($this->getTable().'.clinic_id', $clinicId);
    }
}
