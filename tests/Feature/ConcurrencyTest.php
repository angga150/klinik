<?php

namespace Tests\Feature;

use App\Actions\Billing\BillingWorkflow;
use App\Actions\Clinical\ClinicalWorkflow;
use App\Actions\Inventory\InventoryWorkflow;
use App\Actions\Pharmacy\DispensePrescription;
use App\Models\Diagnosis;
use App\Models\DispenseAllocation;
use App\Models\MedicalStaff;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Polyclinic;
use App\Models\QueueEntry;
use App\Models\ServiceTariff;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_numbers_registration_dispense_and_payment(): void
    {
        $this->seed(CoreSeeder::class);
        $this->race('patient', ['user' => 'pendaftaran', 'input' => ['name' => 'Concurrent Patient', 'birth_date' => '1990-01-01', 'sex' => 'L', 'payer' => 'Umum']]);
        $this->assertSame(2, Patient::distinct()->count('medical_number'));
        $this->race('register', ['user' => 'pendaftaran', 'input' => ['patient_id' => Patient::first()->id, 'polyclinic_id' => Polyclinic::first()->id, 'medical_staff_id' => MedicalStaff::where('profession', 'Dokter')->first()->id, 'service_tariff_id' => ServiceTariff::where('kind', 'consultation')->first()->id, 'payer' => 'Umum']]);
        $this->assertSame([1, 2], QueueEntry::orderBy('number')->pluck('number')->all());
        $nurse = User::where('email', 'perawat@klinik.test')->first();
        $doctor = User::where('email', 'dokter@klinik.test')->first();
        $pharmacist = User::where('email', 'apoteker@klinik.test')->first();
        $cashier = User::where('email', 'kasir@klinik.test')->first();
        $visit = Visit::first();
        $batch = app(InventoryWorkflow::class)->receive($pharmacist, ['medicine_id' => Medicine::first()->id, 'supplier_id' => Supplier::first()->id, 'batch_number' => 'RACE', 'expires_on' => today()->addDays(30)->toDateString(), 'quantity' => 10, 'purchase_price' => '500']);
        $clinical = app(ClinicalWorkflow::class);
        $clinical->triage($nurse, $visit, ['complaint' => 'Test', 'systolic' => 120, 'diastolic' => 80, 'temperature' => 36.5, 'weight' => 60, 'height' => 165, 'pulse' => 80, 'respiration' => 18, 'oxygen' => 98]);
        $clinical->start($doctor, $visit->fresh());
        $clinical->save($doctor, $visit->fresh(), ['subjective' => 'Test', 'objective' => 'Test', 'assessment' => 'Test', 'plan' => 'Test', 'diagnosis_ids' => [Diagnosis::first()->id], 'procedure_ids' => [], 'medicines' => [['medicine_id' => Medicine::first()->id, 'quantity' => 6, 'dose' => '1 tablet', 'frequency' => '3 kali', 'duration' => '2 hari', 'instructions' => 'Test']]], true);
        $prescription = $visit->fresh()->prescription;
        $dispense = app(DispensePrescription::class);
        $dispense->transition($pharmacist, $prescription, 'processing');
        $dispense->transition($pharmacist, $prescription->fresh(), 'ready');
        $this->race('dispense', ['user' => 'apoteker', 'id' => $prescription->id]);
        $this->assertSame(4, $batch->fresh()->quantity);
        $this->assertSame(1, DispenseAllocation::count());
        $invoice = $visit->invoices()->first();
        app(BillingWorkflow::class)->issue($cashier, $invoice);
        $this->race('pay', ['user' => 'kasir', 'id' => $invoice->id, 'input' => ['payment_method_id' => PaymentMethod::where('code', 'cash')->value('id'), 'received' => '100000', 'idempotency_key' => (string) Str::uuid()]]);
        $this->assertSame(1, Payment::count());
        $this->assertSame('completed', $visit->fresh()->status);
    }

    private function race(string $mode, array $data): void
    {
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'clinic-race-'.Str::uuid();
        $workers = [];
        $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => config('database.connections.mysql.host'), 'DB_PORT' => (string) config('database.connections.mysql.port'), 'DB_DATABASE' => config('database.connections.mysql.database'), 'DB_USERNAME' => config('database.connections.mysql.username'), 'DB_PASSWORD' => config('database.connections.mysql.password') ?? '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync'];
        try {
            for ($i = 0; $i < 2; $i++) {
                $p = new Process([PHP_BINARY, base_path('tests/Support/race-worker.php'), $mode, json_encode($data), $barrier, (string) $i], base_path(), $env, timeout: 60);
                $p->start();
                $workers[] = $p;
            }
            $deadline = microtime(true) + 40;
            while (! file_exists($barrier.'.0') || ! file_exists($barrier.'.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Workers did not reach barrier: '.implode(' ', array_map(fn ($p) => $p->getErrorOutput(), $workers)));
                }usleep(20000);
            }
            file_put_contents($barrier.'.go', 'go');
            foreach ($workers as $p) {
                $p->wait();
                $this->assertSame(0, $p->getExitCode(), $p->getErrorOutput());
            }
        } finally {
            foreach ($workers as $p) {
                if ($p->isRunning()) {
                    $p->stop();
                }
            }foreach (['.0', '.1', '.go'] as $suffix) {
                if (file_exists($barrier.$suffix)) {
                    unlink($barrier.$suffix);
                }
            }
        }
    }
}
