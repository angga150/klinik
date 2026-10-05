<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class Audit
{
    public static function record(User $user, string $action, Model $subject, ?string $reason = null): void
    {
        AuditLog::create(['clinic_id' => $user->clinic_id, 'user_id' => $user->id, 'action' => $action, 'subject_type' => class_basename($subject), 'subject_id' => $subject->id, 'reason' => $reason]);
    }
}
