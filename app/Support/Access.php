<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

final class Access
{
    public static function check(User $user, string $permission, ?Model $record = null): void
    {
        abort_unless($user->active && $user->clinic_id && $user->clinic?->active, 403);
        Gate::forUser($user)->authorize($permission);
        if ($record) {
            abort_unless((int) $record->clinic_id === (int) $user->clinic_id, 403);
        }
    }

    public static function active(User $user, string $model, int $id): Model
    {
        return $model::forClinic($user->clinic_id)->where('active', true)->findOrFail($id);
    }
}
