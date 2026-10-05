<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    public function view(User $user, Visit $visit): bool
    {
        return $user->active && $user->can('visits.view') && $user->clinic_id === $visit->clinic_id;
    }
}
