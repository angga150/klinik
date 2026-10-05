<?php

namespace App\Livewire\Clinical;

use App\Actions\Billing\BillingWorkflow;
use App\Actions\Clinical\ClinicalWorkflow;
use App\Actions\Inventory\InventoryWorkflow;
use App\Actions\Pharmacy\DispensePrescription;
use App\Actions\Visit\VisitWorkflow;
use App\Livewire\Page;
use App\Models\Diagnosis;
use App\Models\DispenseAllocation;
use App\Models\Invoice;
use App\Models\MedicalProcedure;
use App\Models\Medicine;
use App\Models\PaymentMethod;
use App\Models\Visit;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class VisitDetail extends Page
{
    #[Locked]
    public int $visitId;

    public array $vitals = [];

    public array $clinical = ['subjective' => '', 'objective' => '', 'assessment' => '', 'plan' => '', 'diagnosis_ids' => [], 'procedure_ids' => [], 'medicines' => []];

    public string $reason = '';

    public string $amendment = '';

    public string $discount = '0';

    public string $received = '';

    public string $reference = '';

    public string $paymentMethod = '';

    public string $returnId = '';

    public string $allocationId = '';

    public int $returnQuantity = 1;

    #[Locked]
    public string $paymentKey;

    public function mount(Visit $visit): void
    {
        $this->authorize('view', $visit);
        $this->visitId = $visit->id;
        $this->paymentKey = (string) Str::uuid();
        if (auth()->user()->can('medical-records.view') && $visit->record) {
            $r = $visit->record;
            $this->clinical = array_merge($this->clinical, $r->only(['subjective', 'objective', 'assessment', 'plan', 'history', 'physical_exam', 'advice']));
            $this->clinical['control_date'] = $r->control_date?->format('Y-m-d');
            $this->clinical['diagnosis_ids'] = $r->diagnoses->pluck('diagnosis_id')->all();
            $this->clinical['procedure_ids'] = $r->procedures->pluck('medical_procedure_id')->all();
            $this->clinical['medicines'] = $visit->prescription?->items->map->only(['medicine_id', 'quantity', 'dose', 'frequency', 'duration', 'instructions'])->all() ?? [];
        }
    }

    private function visit(): Visit
    {
        $v = Visit::forClinic($this->clinic())->findOrFail($this->visitId);
        $this->authorize('view', $v);

        return $v;
    }

    private function invoice(): Invoice
    {
        return $this->visit()->invoices()->where('status', '!=', 'void')->latest('id')->firstOrFail();
    }

    public function triage(): void
    {
        app(ClinicalWorkflow::class)->triage(auth()->user(), $this->visit(), $this->vitals);
        $this->vitals = [];
        $this->success('Pemeriksaan awal selesai. Pasien menunggu dokter.');
    }

    public function start(): void
    {
        app(ClinicalWorkflow::class)->start(auth()->user(), $this->visit());
        $this->success('Pemeriksaan dokter dimulai.');
    }

    public function addMedicine(): void
    {
        $this->allowed('medical-records.write');
        $this->clinical['medicines'][] = ['medicine_id' => '', 'quantity' => 1, 'dose' => '', 'frequency' => '', 'duration' => '', 'instructions' => ''];
    }

    public function removeMedicine(int $index): void
    {
        $this->allowed('medical-records.write');
        unset($this->clinical['medicines'][$index]);
        $this->clinical['medicines'] = array_values($this->clinical['medicines']);
    }

    public function saveClinical(bool $finalize = false): void
    {
        app(ClinicalWorkflow::class)->save(auth()->user(), $this->visit(), $this->clinical, $finalize);
        $this->success($finalize ? 'Rekam medis difinalisasi.' : 'Draft rekam medis tersimpan.');
    }

    public function amend(): void
    {
        app(ClinicalWorkflow::class)->amend(auth()->user(), $this->visit(), $this->reason, $this->amendment);
        $this->amendment = '';
        $this->reason = '';
        $this->success('Amendment tersimpan; catatan asli tetap utuh.');
    }

    public function queue(string $target): void
    {
        app(VisitWorkflow::class)->transition(auth()->user(), $this->visit(), $target, $this->reason);
        $this->success();
    }

    public function pharmacy(string $target): void
    {
        $p = $this->visit()->prescription;
        abort_unless($p, 404);
        $action = app(DispensePrescription::class);
        if ($target === 'dispensed') {
            $action->execute(auth()->user(), $p);
        } else {
            $action->transition(auth()->user(), $p, $target, $this->reason);
        }$this->success('Status apotek diperbarui.');
    }

    public function setDiscount(): void
    {
        app(BillingWorkflow::class)->discount(auth()->user(), $this->invoice(), $this->discount, $this->reason);
        $this->success();
    }

    public function issue(): void
    {
        app(BillingWorkflow::class)->issue(auth()->user(), $this->invoice());
        $this->received = $this->invoice()->total;
        $this->success('Invoice diterbitkan.');
    }

    public function pay(): void
    {
        app(BillingWorkflow::class)->pay(auth()->user(), $this->invoice(), ['payment_method_id' => $this->paymentMethod, 'received' => $this->received, 'reference' => $this->reference, 'idempotency_key' => $this->paymentKey]);
        $this->paymentKey = (string) Str::uuid();
        $this->success('Pembayaran berhasil. Kunjungan selesai.');
    }

    public function voidPayment(int $id): void
    {
        $p = $this->invoice()->payments()->findOrFail($id);
        app(BillingWorkflow::class)->voidPayment(auth()->user(), $p, $this->reason);
        $this->success('Pembayaran dibatalkan dan saldo dibuka kembali.');
    }

    public function replaceInvoice(): void
    {
        app(BillingWorkflow::class)->replace(auth()->user(), $this->invoice(), $this->reason, $this->returnId ? (int) $this->returnId : null);
        $this->returnId = '';
        $this->success('Invoice pengganti dibuat sebagai draft.');
    }

    public function patientReturn(): void
    {
        $p = $this->visit()->prescription;
        abort_unless($p, 404);
        $allocation = DispenseAllocation::forClinic($this->clinic())->whereHas('item', fn ($q) => $q->where('prescription_id', $p->id))->findOrFail($this->allocationId);
        $r = app(InventoryWorkflow::class)->patientReturn(auth()->user(), $allocation, $this->returnQuantity, $this->reason);
        $this->success('Retur #'.$r->id.' masuk karantina. Koreksi tagihan dilakukan oleh administrator.');
    }

    public function render()
    {
        $v = $this->visit()->load(['patient', 'doctor', 'polyclinic', 'queue']);
        $u = auth()->user();
        $clinical = $u->can('medical-records.view');
        $triage = $u->can('vitals.create');
        $pharmacy = $u->can('prescriptions.view');
        $billing = $u->can('invoices.view');
        if ($clinical || $triage || $pharmacy) {
            $v->load('vitals');
        }
        if ($clinical) {
            $v->load(['record.diagnoses.diagnosis', 'record.procedures', 'record.amendments.author']);
        }
        if ($pharmacy) {
            $v->load(['prescription.items.medicine', 'prescription.items.allocations.batch']);
        }
        $invoice = $billing ? $v->invoices()->where('status', '!=', 'void')->with(['items', 'payments.method', 'payments.void'])->latest('id')->first() : null;
        $history = $clinical ? Visit::forClinic($this->clinic())->where('patient_id', $v->patient_id)->where('id', '!=', $v->id)->with(['record.diagnoses.diagnosis', 'doctor'])->latest('id')->limit(10)->get() : collect();

        return view('livewire.clinical.visit-detail', ['visit' => $v, 'invoice' => $invoice, 'history' => $history, 'diagnoses' => $clinical ? Diagnosis::forClinic($this->clinic())->where('active', true)->get() : collect(), 'procedures' => $clinical ? MedicalProcedure::forClinic($this->clinic())->where('active', true)->get() : collect(), 'medicines' => $clinical ? Medicine::forClinic($this->clinic())->where('active', true)->get() : collect(), 'methods' => $billing ? PaymentMethod::forClinic($this->clinic())->where('active', true)->get() : collect()])->title('Kunjungan '.$v->number);
    }
}
