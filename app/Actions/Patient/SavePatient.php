<?php

namespace App\Actions\Patient;

use App\Models\Patient;
use App\Models\User;
use App\Support\Access;
use App\Support\Audit;
use App\Support\Numbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SavePatient
{
    public function execute(User $user, array $input, ?Patient $patient = null): Patient
    {
        Access::check($user, $patient ? 'patients.update' : 'patients.create', $patient);
        $input['nik'] = ($input['nik'] ?? '') ?: null;
        $data = Validator::make($input, [
            'name' => 'required|string|max:150', 'nik' => ['nullable', 'digits:16', Rule::unique('patients')->ignore($patient?->id)],
            'birth_date' => 'required|date|before_or_equal:today', 'birth_place' => 'nullable|string|max:150', 'sex' => 'required|in:L,P',
            'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:1000', 'blood_type' => 'nullable|in:A,B,AB,O,-',
            'allergies' => 'nullable|string|max:1000', 'emergency_contact' => 'nullable|string|max:200', 'payer' => 'required|string|max:100', 'active' => 'boolean',
        ])->validate();

        return DB::transaction(function () use ($user, $data, $patient) {
            if ($patient) {
                $patient->update($data);
            } else {
                $patient = Patient::create($data + ['clinic_id' => $user->clinic_id, 'medical_number' => 'RM-'.$user->clinic_id.'-'.str_pad((string) Numbers::next($user->clinic_id, 'RM'), 6, '0', STR_PAD_LEFT)]);
            }
            Audit::record($user, 'patient.saved', $patient);

            return $patient;
        }, 3);
    }
}
