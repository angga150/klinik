<?php

namespace App\Actions\Identity;

use App\Models\User;
use App\Support\Access;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaveMaster
{
    public function execute(User $user, string $resource, array $input, ?int $id = null): void
    {
        Access::check($user, 'settings.manage');
        $config = config('masters.'.$resource);
        abort_unless($config, 404);
        $model = $config['model'];
        $record = $id ? $model::forClinic($user->clinic_id)->findOrFail($id) : new $model;
        $rules = [];
        foreach ($config['fields'] as $key => $field) {
            $rules[$key] = explode('|', $field['rules']);
            if (in_array($key, ['sku', 'code'])) {
                $rules[$key][] = Rule::unique($resource, $key)->where('clinic_id', $user->clinic_id)->ignore($id);
            }
        }
        $rules['active'] = 'required|boolean';
        $d = Validator::make($input, $rules)->validate();
        foreach ($config['fields'] as $key => $field) {
            if (class_exists('App\\Models\\'.$field['type'])) {
                $related = 'App\\Models\\'.$field['type'];
                $query = $related::where('clinic_id', $user->clinic_id)->where('active', true);
                $linked = $query->findOrFail($d[$key]);
                if ($key === 'medical_staff_id' && $linked->profession !== 'Dokter') {
                    throw ValidationException::withMessages([$key => 'Pilih tenaga medis dokter.']);
                }
            }
        }
        DB::transaction(function () use ($user, $record, $d) {
            $record->fill($d + ['clinic_id' => $user->clinic_id])->save();
            Audit::record($user, 'master.saved', $record);
        }, 3);
    }
}
