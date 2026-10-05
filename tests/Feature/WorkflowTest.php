<?php

namespace Tests\Feature;

use App\Actions\Billing\BillingWorkflow;
use App\Actions\Clinical\ClinicalWorkflow;
use App\Actions\Inventory\InventoryWorkflow;
use App\Actions\Pharmacy\DispensePrescription;
use App\Actions\Visit\VisitWorkflow;
use App\Models\DispenseAllocation;
use App\Models\InvoiceItem;
use App\Models\MedicalRecordAmendment;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Payment;
use App\Models\PaymentVoid;
use App\Models\ServiceTariff;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

class WorkflowTest extends ClinicTestCase
{
    public function test_end_to_end_fefo_billing_payment_and_idempotency(): void
    {
        $later = $this->batch('LATE', 20, 90);
        $early = $this->batch('EARLY', 4, 10);
        $v = $this->clinical();
        $this->assertSame(24, (int) MedicineBatch::sum('quantity'));
        $this->dispense($v);
        $this->assertSame(0, $early->fresh()->quantity);
        $this->assertSame(18, $later->fresh()->quantity);
        $this->assertSame(2, DispenseAllocation::count());
        app(DispensePrescription::class)->execute($this->user('apoteker'), $v->prescription->fresh());
        $this->assertSame(2, DispenseAllocation::count());
        $invoice = $v->invoices()->first();
        $billing = app(BillingWorkflow::class);
        $billing->issue($this->user('kasir'), $invoice);
        $this->assertSame('99000.00', $invoice->fresh()->total);
        $data = $this->paymentData();
        $p = $billing->pay($this->user('kasir'), $invoice, $data);
        $again = $billing->pay($this->user('kasir'), $invoice, $data);
        $this->assertSame($p->id, $again->id);
        $this->assertSame('1000.00', $p->change);
        $this->assertSame('completed', $v->fresh()->status);
        $this->assertSame(1, Payment::count());
        $this->assertSame(18, (int) StockMovement::sum('quantity'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'prescription.dispensed']);
    }

    public function test_shortage_rolls_back_all_batch_allocations_and_billing(): void
    {
        $b = $this->batch('SHORT', 4);
        $v = $this->clinical();
        try {
            $this->dispense($v);
            $this->fail('Expected shortage');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('tidak cukup', $e->getMessage());
        }
        $this->assertSame(4, $b->fresh()->quantity);
        $this->assertSame(0, DispenseAllocation::count());
        $this->assertSame(0, InvoiceItem::where('source_key', 'like', 'prescription-item:%')->count());
        $this->assertSame('ready', $v->prescription->fresh()->status);
    }

    public function test_expired_and_quarantined_batches_are_not_dispensed(): void
    {
        $expired = $this->batch('EXPIRED', 20);
        $expired->update(['expires_on' => today()]);
        $quarantine = $this->batch('QUARANTINE', 20);
        $quarantine->update(['condition' => 'quarantine']);
        $v = $this->clinical();
        try {
            $this->dispense($v);
            $this->fail('Expected shortage');
        } catch (ValidationException) {
        }
        $this->assertSame(20, $expired->fresh()->quantity);
        $this->assertSame(20, $quarantine->fresh()->quantity);
    }

    public function test_final_record_is_immutable_but_amendment_preserves_history(): void
    {
        $v = $this->clinical(false);
        $original = $v->record->subjective;
        try {
            app(ClinicalWorkflow::class)->save($this->user('dokter'), $v, $this->clinicalData(false));
            $this->fail('Must reject');
        } catch (ValidationException) {
        }
        app(ClinicalWorkflow::class)->amend($this->user('dokter'), $v, 'Koreksi catatan', 'Catatan tambahan untuk rekam medis.');
        $this->assertSame($original, $v->record->fresh()->subjective);
        $this->assertSame('amended', $v->record->fresh()->status);
        $this->assertSame(1, MedicalRecordAmendment::count());
    }

    public function test_void_reopens_balance_without_changing_stock_and_can_repay(): void
    {
        $this->batch('VOID');
        $v = $this->clinical();
        $this->dispense($v);
        $i = $v->invoices()->first();
        $b = app(BillingWorkflow::class);
        $b->issue($this->user('kasir'), $i);
        $p = $b->pay($this->user('kasir'), $i, $this->paymentData());
        $b->voidPayment($this->user('admin'), $p, 'Kesalahan metode pembayaran');
        $b->voidPayment($this->user('admin'), $p, 'Kesalahan metode pembayaran');
        $this->assertSame('issued', $i->fresh()->status);
        $this->assertSame('waiting_payment', $v->fresh()->status);
        $this->assertSame(1, PaymentVoid::count());
        $this->assertSame(4, (int) MedicineBatch::sum('quantity'));
        $b->pay($this->user('kasir'), $i, $this->paymentData());
        $this->assertSame('completed', $v->fresh()->status);
    }

    public function test_patient_return_is_quarantined_and_invoice_replacement_uses_original_price(): void
    {
        $b = $this->batch('RETURN');
        $v = $this->clinical();
        $this->dispense($v);
        $i = $v->invoices()->first();
        $billing = app(BillingWorkflow::class);
        $billing->issue($this->user('kasir'), $i);
        $r = app(InventoryWorkflow::class)->patientReturn($this->user('apoteker'), DispenseAllocation::first(), 2, 'Kemasan dikembalikan pasien');
        Medicine::first()->update(['selling_price' => '9000']);
        $new = $billing->replace($this->user('admin'), $i, 'Koreksi sesuai retur pasien', $r->id);
        $billing->issue($this->user('kasir'), $new);
        $this->assertSame('void', $i->fresh()->status);
        $this->assertSame('96000.00', $new->fresh()->total);
        $this->assertSame(4, $b->fresh()->quantity);
        $this->assertSame(2, MedicineBatch::where('condition', 'quarantine')->first()->quantity);
        $this->assertSame('99000.00', $i->fresh()->total);
    }

    public function test_inventory_opname_and_supplier_return_reconcile(): void
    {
        $b = $this->batch('COUNT', 10);
        $a = app(InventoryWorkflow::class);
        $a->adjust($this->user('apoteker'), $b, 8, 'Hasil penghitungan fisik', true);
        $a->supplierReturn($this->user('apoteker'), $b, 2, 'Kemasan rusak dari supplier');
        $this->assertSame(6, $b->fresh()->quantity);
        $this->assertSame(6, (int) $b->movements()->sum('quantity'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'inventory.opname']);
    }

    public function test_invoice_cannot_issue_before_services_complete(): void
    {
        $v = $this->visit();
        $this->expectException(ValidationException::class);
        app(BillingWorkflow::class)->issue($this->user('kasir'), $v->invoices()->first());
    }

    public function test_partial_payment_is_rejected(): void
    {
        $v = $this->clinical(false);
        $i = $v->invoices()->first();
        $b = app(BillingWorkflow::class);
        $b->issue($this->user('kasir'), $i);
        $this->expectException(ValidationException::class);
        $b->pay($this->user('kasir'), $i, $this->paymentData('1000'));
    }

    public function test_patient_nik_is_unique_but_empty_nik_is_allowed(): void
    {
        $a = $this->patient();
        $b = $this->patient();
        $this->assertNotSame($a->medical_number, $b->medical_number);
        $this->patient(['nik' => '1234567890123456']);
        $this->expectException(ValidationException::class);
        $this->patient(['nik' => '1234567890123456']);
    }

    public function test_queue_sequence_and_cancel_are_consistent(): void
    {
        $a = $this->visit();
        $b = $this->visit();
        $this->assertSame(1, $a->queue->number);
        $this->assertSame(2, $b->queue->number);
        app(VisitWorkflow::class)->transition($this->user('pendaftaran'), $a, 'cancelled', 'Pasien membatalkan kunjungan');
        $this->assertSame('cancelled', $a->fresh()->status);
        $this->assertSame('cancelled', $a->queue->fresh()->status);
        $this->assertSame('void', $a->invoices()->first()->status);
    }

    public function test_tariff_changes_do_not_reprice_registered_invoice(): void
    {
        $v = $this->clinical(false);
        ServiceTariff::where('kind', 'consultation')->update(['price' => '999999']);
        $i = $v->invoices()->first();
        app(BillingWorkflow::class)->issue($this->user('kasir'), $i);
        $this->assertSame('90000.00', $i->fresh()->total);
    }
}
