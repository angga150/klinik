<?php

namespace Database\Seeders;

use App\Actions\Billing\BillingWorkflow;
use App\Actions\Clinical\ClinicalWorkflow;
use App\Actions\Inventory\InventoryWorkflow;
use App\Actions\Patient\SavePatient;
use App\Actions\Pharmacy\DispensePrescription;
use App\Actions\Visit\VisitWorkflow;
use App\Models\Diagnosis;
use App\Models\MedicalProcedure;
use App\Models\MedicalStaff;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\Polyclinic;
use App\Models\ServiceTariff;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Visit::exists()) {
            return;
        }
        $users = User::all()->keyBy('email');
        $admin = $users['superadmin@klinik.test'];
        $nurse = $users['perawat@klinik.test'];
        $doctor = $users['dokter@klinik.test'];
        $pharmacist = $users['apoteker@klinik.test'];
        $cashier = $users['kasir@klinik.test'];
        $now = now()->copy();
        $inventory = app(InventoryWorkflow::class);
        $supplier = Supplier::first();
        foreach (Medicine::all() as $i => $medicine) {
            $inventory->receive($pharmacist, ['medicine_id' => $medicine->id, 'supplier_id' => $supplier->id, 'batch_number' => 'DEMO-A-'.$medicine->sku, 'expires_on' => $now->copy()->addDays(25 + $i * 35)->toDateString(), 'quantity' => $i === 4 ? 12 : 400, 'purchase_price' => $medicine->purchase_price, 'notes' => 'Persediaan demo sintetis']);
            $inventory->receive($pharmacist, ['medicine_id' => $medicine->id, 'supplier_id' => $supplier->id, 'batch_number' => 'DEMO-B-'.$medicine->sku, 'expires_on' => $now->copy()->addDays(240)->toDateString(), 'quantity' => $i === 4 ? 0 + 1 : 200, 'purchase_price' => $medicine->purchase_price]);
        }
        $expired = MedicineBatch::create(['clinic_id' => $admin->clinic_id, 'medicine_id' => Medicine::first()->id, 'batch_number' => 'DEMO-EXPIRED', 'expires_on' => $now->copy()->subDays(10), 'purchase_price' => '500', 'quantity' => 15]);
        StockMovement::create(['clinic_id' => $admin->clinic_id, 'medicine_batch_id' => $expired->id, 'user_id' => $pharmacist->id, 'quantity' => 15, 'kind' => 'opening', 'source_key' => 'demo:expired', 'reason' => 'Saldo awal batch kedaluwarsa demo']);
        $names = ['Andi Saputra', 'Dewi Kartika', 'Rizky Pratama', 'Salsabila Putri', 'Agus Setiawan', 'Nur Aisyah', 'Fajar Hidayat', 'Intan Permata', 'Bambang Suryanto', 'Rina Puspita', 'Yoga Prakoso', 'Nadia Safitri', 'Dimas Ramadhan', 'Fitri Handayani', 'Arif Wibowo', 'Lestari Ningsih', 'Hendra Gunawan', 'Putri Maharani', 'Eko Susanto', 'Ayu Anggraini', 'Ilham Maulana', 'Sari Wulandari', 'Reza Firmansyah', 'Tiara Amelia', 'Dedi Kurniawan', 'Nina Oktaviani', 'Wahyu Nugroho', 'Rani Febriani', 'Bayu Setiawan', 'Maya Anjani', 'Farhan Akbar', 'Ratna Dewi'];
        foreach ($names as $i => $name) {
            app(SavePatient::class)->execute($admin, ['name' => $name, 'birth_date' => (1980 + $i % 23).'-'.str_pad((string) ($i % 12 + 1), 2, '0', STR_PAD_LEFT).'-12', 'sex' => $i % 2 ? 'P' : 'L', 'phone' => '08000000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'address' => 'Alamat fiktif '.$i.', Jakarta', 'payer' => 'Umum', 'active' => true]);
        }
        $patients = Patient::all();
        $poly = Polyclinic::first();
        $staff = MedicalStaff::where('profession', 'Dokter')->first();
        $tariff = ServiceTariff::where('kind', 'consultation')->first();
        $medicine = Medicine::first();
        try {
            for ($i = 0; $i < 44; $i++) {
                $day = $i < 30 ? $now->copy()->subDays(1 + intdiv($i, 2)) : $now->copy();
                Carbon::setTestNow($day->setTime(8 + ($i % 9), $i % 60));
                $v = app(VisitWorkflow::class)->register($admin, ['patient_id' => $patients[$i % count($patients)]->id, 'polyclinic_id' => $poly->id, 'medical_staff_id' => $staff->id, 'service_tariff_id' => $tariff->id, 'payer' => 'Umum']);
                if ($i >= 30 && $i % 7 === 0) {
                    continue;
                }
                app(ClinicalWorkflow::class)->triage($nurse, $v, ['complaint' => 'Keluhan fiktif untuk demonstrasi pelayanan', 'systolic' => 120, 'diastolic' => 80, 'temperature' => 36.7, 'weight' => 60 + $i % 20, 'height' => 165, 'pulse' => 78, 'respiration' => 18, 'oxygen' => 98, 'allergies' => 'Tidak ada alergi yang diketahui']);
                if ($i >= 30 && $i % 7 === 1) {
                    continue;
                }
                Carbon::setTestNow(now()->addMinutes(12 + $i % 15));
                app(ClinicalWorkflow::class)->start($doctor, $v->fresh());
                if ($i >= 30 && $i % 7 === 2) {
                    continue;
                }
                app(ClinicalWorkflow::class)->save($doctor, $v->fresh(), ['subjective' => 'Pasien demo mengeluhkan sakit kepala ringan.', 'objective' => 'Kondisi umum baik. Data simulasi.', 'assessment' => 'Sakit kepala; skenario fiktif.', 'plan' => 'Edukasi dan terapi sesuai skenario demo.', 'diagnosis_ids' => [Diagnosis::where('code', 'R51')->value('id')], 'procedure_ids' => $i % 3 === 0 ? [MedicalProcedure::first()->id] : [], 'medicines' => [['medicine_id' => $medicine->id, 'quantity' => 6, 'dose' => '1 tablet', 'frequency' => '3 kali sehari', 'duration' => '2 hari', 'instructions' => 'Sesudah makan; data simulasi']]], true);
                if ($i >= 30 && $i % 7 === 3) {
                    continue;
                }
                $ph = app(DispensePrescription::class);
                $rx = $v->fresh()->prescription;
                $ph->transition($pharmacist, $rx, 'processing');
                $ph->transition($pharmacist, $rx->fresh(), 'ready');
                $ph->execute($pharmacist, $rx->fresh());
                if ($i >= 30 && $i % 7 === 4) {
                    continue;
                }
                $invoice = $v->invoices()->first();
                app(BillingWorkflow::class)->issue($cashier, $invoice);
                if ($i >= 30 && $i % 7 === 5) {
                    continue;
                }
                $invoice->refresh();
                app(BillingWorkflow::class)->pay($cashier, $invoice, ['payment_method_id' => PaymentMethod::where('code', 'cash')->value('id'), 'received' => $invoice->total, 'idempotency_key' => (string) Str::uuid()]);
            }
        } finally {
            Carbon::setTestNow();
        }
    }
}
