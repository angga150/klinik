<?php

namespace App\Actions\Visit;

use App\Enums\VisitStatus;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MedicalStaff;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\QueueEntry;
use App\Models\ServiceTariff;
use App\Models\User;
use App\Models\Visit;
use App\Support\Access;
use App\Support\Audit;
use App\Support\Numbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VisitWorkflow
{
    public function register(User $user, array $input): Visit
    {
        Access::check($user, 'visits.register');
        $d = Validator::make($input, ['patient_id' => 'required|integer', 'polyclinic_id' => 'required|integer', 'medical_staff_id' => 'required|integer', 'service_tariff_id' => 'required|integer', 'payer' => 'required|string|max:100'])->validate();

        return DB::transaction(function () use ($user, $d) {
            Access::active($user, Patient::class, $d['patient_id']);
            Access::active($user, Polyclinic::class, $d['polyclinic_id']);
            $doctor = Access::active($user, MedicalStaff::class, $d['medical_staff_id']);
            $tariff = Access::active($user, ServiceTariff::class, $d['service_tariff_id']);
            $schedule = DoctorSchedule::forClinic($user->clinic_id)->where('medical_staff_id', $doctor->id)->where('polyclinic_id', $d['polyclinic_id'])->where('weekday', now()->dayOfWeek)->where('active', true)->lockForUpdate()->first();
            if (! $schedule || $doctor->profession !== 'Dokter' || $tariff->kind !== 'consultation') {
                throw ValidationException::withMessages(['registration' => 'Jadwal dokter atau tarif konsultasi tidak sesuai.']);
            }
            $count = Visit::forClinic($user->clinic_id)->where('medical_staff_id', $doctor->id)->where('polyclinic_id', $d['polyclinic_id'])->whereDate('visit_date', today())->whereNotIn('status', ['cancelled', 'no_show'])->count();
            if ($count >= $schedule->capacity) {
                throw ValidationException::withMessages(['registration' => 'Kuota dokter hari ini penuh.']);
            }
            $visit = Visit::create($d + ['clinic_id' => $user->clinic_id, 'number' => Numbers::document($user->clinic_id, 'VIS'), 'visit_date' => today(), 'status' => VisitStatus::WaitingTriage->value]);
            QueueEntry::create(['clinic_id' => $user->clinic_id, 'visit_id' => $visit->id, 'polyclinic_id' => $d['polyclinic_id'], 'queue_date' => today(), 'number' => Numbers::next($user->clinic_id, 'Q:'.today()->toDateString().':'.$d['polyclinic_id']), 'status' => 'waiting']);
            $invoice = Invoice::create(['clinic_id' => $user->clinic_id, 'visit_id' => $visit->id, 'number' => Numbers::document($user->clinic_id, 'INV')]);
            foreach (ServiceTariff::forClinic($user->clinic_id)->where('active', true)->where(fn ($q) => $q->where('id', $tariff->id)->orWhere('kind', 'administration'))->get() as $item) {
                InvoiceItem::create(['clinic_id' => $user->clinic_id, 'invoice_id' => $invoice->id, 'source_key' => 'tariff:'.$item->id, 'description' => $item->name, 'quantity' => 1, 'unit_price' => $item->price, 'total' => $item->price]);
            }
            Audit::record($user, 'visit.registered', $visit);

            return $visit;
        }, 3);
    }

    public function transition(User $user, Visit $visit, string $target, string $reason = ''): void
    {
        Access::check($user, 'queues.manage', $visit);
        Validator::make(['target' => $target, 'reason' => $reason], ['target' => 'required|in:called,cancelled,no_show', 'reason' => 'required_unless:target,called|string|max:1000'])->validate();
        DB::transaction(function () use ($user, $visit, $target, $reason) {
            $v = Visit::lockForUpdate()->findOrFail($visit->id);
            if (! in_array($v->status, ['waiting_triage', 'waiting_doctor'])) {
                throw ValidationException::withMessages(['queue' => 'Status kunjungan tidak mengizinkan perubahan ini.']);
            }
            if ($target === 'called') {
                $v->queue()->update(['status' => 'called', 'called_at' => now()]);
            } else {
                $v->update(['status' => $target, 'cancellation_reason' => $reason]);
                $v->queue()->update(['status' => $target]);
                $v->invoices()->where('status', 'draft')->update(['status' => 'void', 'void_reason' => $reason]);
            }
            Audit::record($user, 'queue.'.$target, $v, $reason);
        }, 3);
    }
}
