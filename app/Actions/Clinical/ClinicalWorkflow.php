<?php

namespace App\Actions\Clinical;

use App\Models\Diagnosis;
use App\Models\InvoiceItem;
use App\Models\MedicalProcedure;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAmendment;
use App\Models\MedicalRecordDiagnosis;
use App\Models\MedicalRecordProcedure;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use App\Models\VitalSign;
use App\Support\Access;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ClinicalWorkflow
{
    public function triage(User $user, Visit $visit, array $input): void
    {
        Access::check($user, 'vitals.create', $visit);
        $d = Validator::make($input, ['complaint' => 'required|string|max:5000', 'systolic' => 'required|integer|between:30,300', 'diastolic' => 'required|integer|between:20,200', 'temperature' => 'required|numeric|between:25,45', 'weight' => 'required|numeric|between:1,500', 'height' => 'required|numeric|between:30,250', 'pulse' => 'required|integer|between:20,300', 'respiration' => 'required|integer|between:1,100', 'oxygen' => 'required|integer|between:1,100', 'allergies' => 'nullable|string|max:1000', 'notes' => 'nullable|string|max:5000'])->validate();
        DB::transaction(function () use ($user, $visit, $d) {
            $v = Visit::lockForUpdate()->findOrFail($visit->id);
            $this->state($v, ['waiting_triage']);
            VitalSign::create($d + ['clinic_id' => $user->clinic_id, 'visit_id' => $v->id, 'user_id' => $user->id, 'bmi' => round($d['weight'] / (($d['height'] / 100) ** 2), 2)]);
            $v->update(['status' => 'waiting_doctor']);
            $v->queue()->update(['status' => 'triage']);
            Audit::record($user, 'triage.saved', $v);
        }, 3);
    }

    public function start(User $user, Visit $visit): void
    {
        $this->doctor($user, $visit, 'medical-records.write');
        DB::transaction(function () use ($user, $visit) {
            $v = Visit::lockForUpdate()->findOrFail($visit->id);
            $this->state($v, ['waiting_doctor']);
            $v->update(['status' => 'in_consultation']);
            $v->queue()->update(['status' => 'in_consultation', 'consultation_at' => now()]);
            Audit::record($user, 'consultation.started', $v);
        }, 3);
    }

    public function save(User $user, Visit $visit, array $input, bool $finalize = false): void
    {
        $this->doctor($user, $visit, $finalize ? 'medical-records.finalize' : 'medical-records.write');
        $d = Validator::make($input, [
            'subjective' => 'required|string|max:10000', 'objective' => 'required|string|max:10000', 'assessment' => 'required|string|max:10000', 'plan' => 'required|string|max:10000',
            'history' => 'nullable|string|max:10000', 'physical_exam' => 'nullable|string|max:10000', 'advice' => 'nullable|string|max:5000', 'control_date' => 'nullable|date|after_or_equal:today',
            'diagnosis_ids' => ($finalize ? 'required' : 'present').'|array', 'diagnosis_ids.*' => 'integer|distinct',
            'procedure_ids' => 'present|array', 'procedure_ids.*' => 'integer|distinct', 'medicines' => 'present|array',
            'medicines.*.medicine_id' => 'required|integer|distinct', 'medicines.*.quantity' => 'required|integer|min:1|max:10000', 'medicines.*.dose' => 'required|string|max:100',
            'medicines.*.frequency' => 'required|string|max:100', 'medicines.*.duration' => 'required|string|max:100', 'medicines.*.instructions' => 'required|string|max:255',
        ])->validate();
        DB::transaction(function () use ($user, $visit, $d, $finalize) {
            $v = Visit::lockForUpdate()->findOrFail($visit->id);
            $this->state($v, ['in_consultation']);
            $record = $v->record;
            if ($record && $record->status !== 'draft') {
                throw ValidationException::withMessages(['clinical' => 'Rekam medis sudah dikunci. Gunakan amendment.']);
            }
            $fields = collect($d)->except(['diagnosis_ids', 'procedure_ids', 'medicines'])->all();
            $fields['control_date'] = ($fields['control_date'] ?? null) ?: null;
            $record = MedicalRecord::updateOrCreate(['visit_id' => $v->id], $fields + ['clinic_id' => $user->clinic_id, 'user_id' => $user->id, 'status' => $finalize ? 'finalized' : 'draft', 'finalized_at' => $finalize ? now() : null]);
            $record->diagnoses()->delete();
            $record->procedures()->delete();
            foreach ($d['diagnosis_ids'] as $i => $id) {
                Access::active($user, Diagnosis::class, $id);
                MedicalRecordDiagnosis::create(['clinic_id' => $user->clinic_id, 'medical_record_id' => $record->id, 'diagnosis_id' => $id, 'primary' => $i === 0]);
            }
            $invoice = $v->invoices()->where('status', 'draft')->firstOrFail();
            foreach ($d['procedure_ids'] as $id) {
                $procedure = Access::active($user, MedicalProcedure::class, $id);
                $line = MedicalRecordProcedure::create(['clinic_id' => $user->clinic_id, 'medical_record_id' => $record->id, 'medical_procedure_id' => $id, 'description' => $procedure->name, 'price' => $procedure->price, 'quantity' => 1]);
                if ($finalize) {
                    InvoiceItem::create(['clinic_id' => $user->clinic_id, 'invoice_id' => $invoice->id, 'source_key' => 'procedure:'.$line->id, 'description' => $procedure->name, 'quantity' => 1, 'unit_price' => $procedure->price, 'total' => $procedure->price]);
                }
            }
            $prescription = Prescription::updateOrCreate(['visit_id' => $v->id], ['clinic_id' => $user->clinic_id, 'user_id' => $user->id, 'status' => $finalize ? (count($d['medicines']) ? 'submitted' : 'cancelled') : 'draft', 'reason' => count($d['medicines']) ? null : 'Tidak ada resep']);
            $prescription->items()->delete();
            foreach ($d['medicines'] as $item) {
                $medicine = Access::active($user, Medicine::class, $item['medicine_id']);
                PrescriptionItem::create($item + ['clinic_id' => $user->clinic_id, 'prescription_id' => $prescription->id, 'unit' => $medicine->unit->name]);
            }
            if ($finalize) {
                $v->update(['status' => count($d['medicines']) ? 'waiting_pharmacy' : 'waiting_payment']);
                $v->queue()->update(['status' => 'completed']);
            }
            Audit::record($user, $finalize ? 'medical-record.finalized' : 'medical-record.draft', $record);
        }, 3);
    }

    public function amend(User $user, Visit $visit, string $reason, string $content): void
    {
        $this->doctor($user, $visit, 'medical-records.write');
        Validator::make(compact('reason', 'content'), ['reason' => 'required|string|min:5|max:1000', 'content' => 'required|string|min:5|max:10000'])->validate();
        DB::transaction(function () use ($user, $visit, $reason, $content) {
            $record = MedicalRecord::where('visit_id', $visit->id)->lockForUpdate()->firstOrFail();
            if (! in_array($record->status, ['finalized', 'amended'])) {
                throw ValidationException::withMessages(['clinical' => 'Finalisasi rekam medis terlebih dahulu.']);
            }
            $amendment = MedicalRecordAmendment::create(['clinic_id' => $user->clinic_id, 'medical_record_id' => $record->id, 'user_id' => $user->id, 'reason' => $reason, 'content' => $content]);
            $record->update(['status' => 'amended']);
            Audit::record($user, 'medical-record.amended', $amendment, $reason);
        }, 3);
    }

    private function doctor(User $user, Visit $visit, string $permission): void
    {
        Access::check($user, $permission, $visit);
        abort_unless($visit->doctor->user_id === $user->id || $user->hasRole('Super Admin'), 403);
    }

    private function state(Visit $v, array $allowed): void
    {
        if (! in_array($v->status, $allowed)) {
            throw ValidationException::withMessages(['clinical' => 'Tahap pelayanan tidak sesuai. Muat ulang kunjungan.']);
        }
    }
}
