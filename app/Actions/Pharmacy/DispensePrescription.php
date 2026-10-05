<?php

namespace App\Actions\Pharmacy;

use App\Models\DispenseAllocation;
use App\Models\InvoiceItem;
use App\Models\MedicineBatch;
use App\Models\Prescription;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Visit;
use App\Support\Access;
use App\Support\Audit;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DispensePrescription
{
    public function transition(User $user, Prescription $prescription, string $target, string $reason = ''): void
    {
        Access::check($user, 'prescriptions.dispense', $prescription);
        Validator::make(compact('target', 'reason'), ['target' => 'required|in:processing,ready,cancelled', 'reason' => 'required_if:target,cancelled|string|max:1000'])->validate();
        DB::transaction(function () use ($user, $prescription, $target, $reason) {
            $visit = Visit::lockForUpdate()->findOrFail($prescription->visit_id);
            $p = Prescription::lockForUpdate()->findOrFail($prescription->id);
            $allowed = ['submitted' => ['processing', 'cancelled'], 'processing' => ['ready', 'cancelled'], 'ready' => ['cancelled']];
            if (! in_array($target, $allowed[$p->status] ?? [])) {
                throw ValidationException::withMessages(['pharmacy' => 'Urutan proses resep tidak sesuai.']);
            }
            $p->update(['status' => $target, 'reason' => $reason]);
            if ($target === 'cancelled') {
                $visit->update(['status' => 'waiting_payment']);
            }
            Audit::record($user, 'prescription.'.$target, $p, $reason);
        }, 3);
    }

    public function execute(User $user, Prescription $prescription): void
    {
        Access::check($user, 'prescriptions.dispense', $prescription);
        DB::transaction(function () use ($user, $prescription) {
            $visit = Visit::lockForUpdate()->findOrFail($prescription->visit_id);
            $p = Prescription::lockForUpdate()->findOrFail($prescription->id);
            if ($p->status === 'dispensed') {
                return;
            }
            if ($p->status !== 'ready' || $visit->status !== 'waiting_pharmacy') {
                throw ValidationException::withMessages(['pharmacy' => 'Resep harus diverifikasi dan siap diserahkan.']);
            }
            $invoice = $visit->invoices()->where('status', 'draft')->lockForUpdate()->firstOrFail();
            foreach ($p->items()->with('medicine')->orderBy('medicine_id')->get() as $item) {
                if (! $item->medicine->active) {
                    throw ValidationException::withMessages(['pharmacy' => 'Obat tidak aktif. Hubungi dokter.']);
                }
                $remaining = $item->quantity;
                $batches = MedicineBatch::forClinic($user->clinic_id)->where('medicine_id', $item->medicine_id)->where('condition', 'usable')->whereDate('expires_on', '>', today())->where('quantity', '>', 0)->orderBy('expires_on')->orderBy('id')->lockForUpdate()->get();
                foreach ($batches as $batch) {
                    $take = min($remaining, $batch->quantity);
                    if (! $take) {
                        break;
                    }
                    $allocation = DispenseAllocation::create(['clinic_id' => $user->clinic_id, 'prescription_item_id' => $item->id, 'medicine_batch_id' => $batch->id, 'quantity' => $take]);
                    $batch->decrement('quantity', $take);
                    StockMovement::create(['clinic_id' => $user->clinic_id, 'medicine_batch_id' => $batch->id, 'user_id' => $user->id, 'quantity' => -$take, 'kind' => 'dispense', 'source_key' => 'dispense:'.$allocation->id, 'reason' => 'Penyerahan resep #'.$p->id]);
                    $remaining -= $take;
                }
                if ($remaining > 0) {
                    throw ValidationException::withMessages(['pharmacy' => 'Stok layak pakai '.$item->medicine->name.' tidak cukup. Tidak ada stok yang dikurangi.']);
                }
                InvoiceItem::create(['clinic_id' => $user->clinic_id, 'invoice_id' => $invoice->id, 'source_key' => 'prescription-item:'.$item->id, 'description' => $item->medicine->name, 'quantity' => $item->quantity, 'unit_price' => $item->medicine->selling_price, 'total' => Money::mul($item->medicine->selling_price, $item->quantity)]);
            }
            $p->update(['status' => 'dispensed', 'dispensed_at' => now(), 'dispensed_by' => $user->id]);
            $visit->update(['status' => 'waiting_payment']);
            Audit::record($user, 'prescription.dispensed', $p);
        }, 3);
    }
}
