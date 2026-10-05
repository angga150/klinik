<?php

namespace App\Models;

class LoginHistory extends ClinicModel
{
    protected $table = 'login_histories';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
