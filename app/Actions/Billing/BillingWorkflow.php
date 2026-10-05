<?php

namespace App\Actions\Billing;

use App\Models\DispenseAllocation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentVoid;
use App\Models\Prescription;
use App\Models\StockReturn;
use App\Models\User;
use App\Models\Visit;
use App\Support\Access;
use App\Support\Audit;
use App\Support\Money;
use App\Support\Numbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BillingWorkflow
{
    public function discount(User $user, Invoice $invoice, string $amount, string $reason): void
    {
        Access::check($user, 'invoices.discount', $invoice);
        Validator::make(compact('amount', 'reason'), ['amount' => 'required|decimal:0,2|min:0|max:999999999', 'reason' => 'required|string|min:5|max:1000'])->validate();
        DB::transaction(function () use ($user, $invoice, $amount, $reason) {
            $i = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->require($i->status === 'draft', 'Diskon hanya pada invoice draft.');
            $sum = $i->items()->sum('total');
            $this->require(bccomp($amount, (string) $sum, 2) <= 0, 'Diskon melebihi tagihan.');
            $i->update(['discount' => $amount, 'discount_reason' => $reason]);
            Audit::record($user, 'invoice.discounted', $i, $reason);
        }, 3);
    }

    public function issue(User $user, Invoice $invoice): void
    {
        Access::check($user, 'payments.create', $invoice);
        DB::transaction(function () use ($user, $invoice) {
            $visit = Visit::lockForUpdate()->findOrFail($invoice->visit_id);
            $i = Invoice::lockForUpdate()->findOrFail($invoice->id);
            if ($i->status === 'issued') {
                return;
            }
            $this->require($i->status === 'draft' && $visit->status === 'waiting_payment', 'Pelayanan belum selesai atau invoice bukan draft.');
            $this->require(in_array($visit->record?->status, ['finalized', 'amended']) && in_array($visit->prescription?->status, ['dispensed', 'cancelled']), 'Rekam medis/resep belum selesai.');
            $sum = (string) $i->items()->sum('total');
            $total = Money::sub($sum, $i->discount);
            $this->require(bccomp($total, '0', 2) >= 0, 'Total tagihan tidak valid.');
            $i->update(['subtotal' => $sum, 'total' => $total, 'status' => 'issued', 'issued_at' => now()]);
            Audit::record($user, 'invoice.issued', $i);
        }, 3);
    }

    public function pay(User $user, Invoice $invoice, array $input): Payment
    {
        Access::check($user, 'payments.create', $invoice);
        $d = Validator::make($input, ['payment_method_id' => 'required|integer', 'received' => 'required|decimal:0,2|min:0|max:9999999999999', 'reference' => 'nullable|string|max:100', 'idempotency_key' => 'required|uuid'])->validate();

        return DB::transaction(function () use ($user, $invoice, $d) {
            $visit = Visit::lockForUpdate()->findOrFail($invoice->visit_id);
            $i = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $existing = Payment::where('idempotency_key', $d['idempotency_key'])->first();
            if ($existing) {
                $this->require($existing->invoice_id === $i->id, 'Kunci transaksi sudah digunakan.');

                return $existing;
            }
            $this->require($i->status === 'issued', 'Invoice harus diterbitkan dan belum dibayar.');
            $method = Access::active($user, PaymentMethod::class, $d['payment_method_id']);
            $this->require($method->code === 'cash' ? bccomp($d['received'], $i->total, 2) >= 0 : bccomp($d['received'], $i->total, 2) === 0, 'Pembayaran harus melunasi tagihan; transfer/QRIS harus tepat.');
            $this->require($method->code === 'cash' || filled($d['reference'] ?? null), 'Nomor referensi transfer/QRIS wajib diisi.');
            $payment = Payment::create(['clinic_id' => $user->clinic_id, 'invoice_id' => $i->id, 'payment_method_id' => $method->id, 'user_id' => $user->id, 'number' => Numbers::document($user->clinic_id, 'PAY'), 'idempotency_key' => $d['idempotency_key'], 'amount' => $i->total, 'received' => $d['received'], 'change' => Money::sub($d['received'], $i->total), 'reference' => $d['reference'] ?? null, 'paid_at' => now()]);
            $i->update(['status' => 'paid']);
            $visit->update(['status' => 'completed', 'completed_at' => now()]);
            Audit::record($user, 'payment.created', $payment);

            return $payment;
        }, 3);
    }

    public function voidPayment(User $user, Payment $payment, string $reason): void
    {
        Access::check($user, 'payments.void', $payment);
        $this->reason($reason);
        DB::transaction(function () use ($user, $payment, $reason) {
            $visit = Visit::lockForUpdate()->findOrFail($payment->invoice->visit_id);
            $i = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);
            $p = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($p->void()->exists()) {
                return;
            }
            $this->require($i->status === 'paid', 'Invoice bukan transaksi lunas.');
            PaymentVoid::create(['clinic_id' => $user->clinic_id, 'payment_id' => $p->id, 'user_id' => $user->id, 'amount' => $p->amount, 'reason' => $reason]);
            $i->update(['status' => 'issued']);
            $visit->update(['status' => 'waiting_payment', 'completed_at' => null]);
            Audit::record($user, 'payment.voided', $p, $reason);
        }, 3);
    }

    public function replace(User $user, Invoice $invoice, string $reason, ?int $returnId = null): Invoice
    {
        Access::check($user, 'payments.void', $invoice);
        $this->reason($reason);

        return DB::transaction(function () use ($user, $invoice, $reason, $returnId) {
            $visit = Visit::lockForUpdate()->findOrFail($invoice->visit_id);
            $i = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->require(in_array($i->status, ['draft', 'issued']), 'Void pembayaran terlebih dahulu.');
            $return = null;
            if ($returnId) {
                $return = StockReturn::forClinic($user->clinic_id)->where('kind', 'patient')->findOrFail($returnId);
                $allocation = DispenseAllocation::with('item')->findOrFail($return->dispense_allocation_id);
                $prescription = Prescription::findOrFail($allocation->item->prescription_id);
                $this->require($prescription->visit_id === $visit->id, 'Retur bukan milik kunjungan ini.');
                $this->require(! Invoice::where('stock_return_id', $returnId)->exists(), 'Retur sudah dikoreksi pada invoice.');
            }
            $i->update(['status' => 'void', 'void_reason' => $reason]);
            $new = Invoice::create(['clinic_id' => $user->clinic_id, 'visit_id' => $visit->id, 'number' => Numbers::document($user->clinic_id, 'INV'), 'replaces_id' => $i->id, 'stock_return_id' => $returnId, 'discount' => $i->discount, 'discount_reason' => $i->discount_reason]);
            foreach ($i->items as $line) {
                $quantity = $line->quantity;
                if ($return && $line->source_key === 'prescription-item:'.$allocation->prescription_item_id) {
                    $quantity -= $return->quantity;
                }
                $this->require($quantity >= 0, 'Jumlah koreksi melebihi item tagihan.');
                if ($quantity) {
                    InvoiceItem::create(['clinic_id' => $user->clinic_id, 'invoice_id' => $new->id, 'source_key' => $line->source_key, 'description' => $line->description, 'quantity' => $quantity, 'unit_price' => $line->unit_price, 'total' => Money::mul($line->unit_price, $quantity)]);
                }
            }
            if (bccomp($new->discount, (string) $new->items()->sum('total'), 2) > 0) {
                $new->update(['discount' => '0', 'discount_reason' => 'Diskon direset setelah koreksi retur']);
            }
            Audit::record($user, 'invoice.replaced', $i, $reason);

            return $new;
        }, 3);
    }

    private function reason(string $reason): void
    {
        Validator::make(compact('reason'), ['reason' => 'required|string|min:5|max:1000'])->validate();
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['billing' => $message]);
        }
    }
}
