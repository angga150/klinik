<?php

namespace Tests\Feature;

use App\Actions\Clinical\ClinicalWorkflow;
use App\Actions\Inventory\InventoryWorkflow;
use App\Actions\Patient\SavePatient;
use App\Actions\Pharmacy\DispensePrescription;
use App\Actions\Visit\VisitWorkflow;
use App\Models\Diagnosis;
use App\Models\MedicalStaff;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\Polyclinic;
use App\Models\ServiceTariff;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class ClinicTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreSeeder::class);
    }

    protected function user(string $role = 'superadmin'): User
    {
        return User::where('email', $role.'@klinik.test')->firstOrFail();
    }

    protected function patient(array $data = []): Patient
    {
        return app(SavePatient::class)->execute($this->user('pendaftaran'), $data + ['name' => 'Pasien Uji', 'birth_date' => '1990-01-01', 'sex' => 'L', 'payer' => 'Umum']);
    }

    protected function visit(?Patient $patient = null): Visit
    {
        return app(VisitWorkflow::class)->register($this->user('pendaftaran'), ['patient_id' => ($patient ?? $this->patient())->id, 'polyclinic_id' => Polyclinic::first()->id, 'medical_staff_id' => MedicalStaff::where('profession', 'Dokter')->first()->id, 'service_tariff_id' => ServiceTariff::where('kind', 'consultation')->first()->id, 'payer' => 'Umum']);
    }

    protected function triageData(): array
    {
        return ['complaint' => 'Keluhan pengujian', 'systolic' => 120, 'diastolic' => 80, 'temperature' => 36.5, 'weight' => 60, 'height' => 165, 'pulse' => 80, 'respiration' => 18, 'oxygen' => 98];
    }

    protected function clinicalData(bool $medicine = true): array
    {
        return ['subjective' => 'Keluhan', 'objective' => 'Pemeriksaan', 'assessment' => 'Diagnosis uji', 'plan' => 'Rencana', 'diagnosis_ids' => [Diagnosis::first()->id], 'procedure_ids' => [], 'medicines' => $medicine ? [['medicine_id' => Medicine::first()->id, 'quantity' => 6, 'dose' => '1 tablet', 'frequency' => '3 kali', 'duration' => '2 hari', 'instructions' => 'Sesudah makan']] : []];
    }

    protected function clinical(bool $medicine = true): Visit
    {
        $v = $this->visit();
        $a = app(ClinicalWorkflow::class);
        $a->triage($this->user('perawat'), $v, $this->triageData());
        $a->start($this->user('dokter'), $v->fresh());
        $a->save($this->user('dokter'), $v->fresh(), $this->clinicalData($medicine), true);

        return $v->fresh();
    }

    protected function batch(string $code, int $quantity = 10, int $days = 30)
    {
        return app(InventoryWorkflow::class)->receive($this->user('apoteker'), ['medicine_id' => Medicine::first()->id, 'supplier_id' => Supplier::first()->id, 'batch_number' => $code, 'expires_on' => today()->addDays($days)->toDateString(), 'quantity' => $quantity, 'purchase_price' => '500']);
    }

    protected function dispense(Visit $v): void
    {
        $a = app(DispensePrescription::class);
        $p = $v->prescription;
        $a->transition($this->user('apoteker'), $p, 'processing');
        $a->transition($this->user('apoteker'), $p->fresh(), 'ready');
        $a->execute($this->user('apoteker'), $p->fresh());
    }

    protected function paymentData(string $amount = '100000'): array
    {
        return ['payment_method_id' => PaymentMethod::where('code', 'cash')->value('id'), 'received' => $amount, 'idempotency_key' => (string) Str::uuid()];
    }
}
