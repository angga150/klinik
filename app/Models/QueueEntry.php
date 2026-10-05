<?php

namespace App\Models;

class QueueEntry extends ClinicModel
{
    protected $table = 'queues';

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}
