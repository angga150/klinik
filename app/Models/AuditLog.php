<?php

namespace App\Models;

class AuditLog extends ClinicModel
{
    protected $table = 'audit_logs';

    public function actor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
