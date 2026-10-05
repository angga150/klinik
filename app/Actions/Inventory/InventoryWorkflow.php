<?php

namespace App\Actions\Inventory;

use App\Models\DispenseAllocation;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockReturn;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Access;
use App\Support\Audit;
use App\Support\Numbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InventoryWorkflow
{
    public function receive(User $user, array $input): MedicineBatch
    {
        Access::check($user, 'inventory.receive');
        $d = Validator::make($input, ['medicine_id' => 'required|integer', 'supplier_id' => 'required|integer', 'batch_number' => 'required|string|max:80', 'expires_on' => 'required|date|after:today', 'quantity' => 'required|integer|between:1,1000000', 'purchase_price' => 'required|decimal:0,2|min:0|max:999999999', 'notes' => 'nullable|string|max:1000'])->validate();

        return DB::transaction(function () use ($user, $d) {
            Access::active($user, Medicine::class, $d['medicine_id']);
            Access::active($user, Supplier::class, $d['supplier_id']);
            if (MedicineBatch::forClinic($user->clinic_id)->where('medicine_id', $d['medicine_id'])->where('batch_number', $d['batch_number'])->where('condition', 'usable')->exists()) {
                throw ValidationException::withMessages(['batch_number' => 'Batch sudah ada. Gunakan kode penerimaan berbeda.']);
            }
            $purchase = Purchase::create(['clinic_id' => $user->clinic_id, 'number' => Numbers::document($user->clinic_id, 'RCV'), 'supplier_id' => $d['supplier_id'], 'user_id' => $user->id, 'received_on' => today(), 'notes' => $d['notes'] ?? null]);
            $batch = MedicineBatch::create(collect($d)->only(['medicine_id', 'batch_number', 'expires_on', 'quantity', 'purchase_price'])->all() + ['clinic_id' => $user->clinic_id, 'purchase_id' => $purchase->id]);
            $item = PurchaseItem::create(['clinic_id' => $user->clinic_id, 'purchase_id' => $purchase->id, 'medicine_batch_id' => $batch->id, 'quantity' => $d['quantity'], 'price' => $d['purchase_price']]);
            $this->movement($user, $batch, $d['quantity'], 'receive', 'purchase:'.$item->id, 'Penerimaan '.$purchase->number);
            Audit::record($user, 'inventory.received', $purchase);

            return $batch;
        }, 3);
    }

    public function adjust(User $user, MedicineBatch $batch, int $count, string $reason, bool $opname = false): void
    {
        Access::check($user, 'inventory.adjust', $batch);
        Validator::make(compact('count', 'reason'), ['count' => 'required|integer|between:0,1000000', 'reason' => 'required|string|min:5|max:1000'])->validate();
        DB::transaction(function () use ($user, $batch, $count, $reason, $opname) {
            $b = MedicineBatch::lockForUpdate()->findOrFail($batch->id);
            $delta = $count - $b->quantity;
            $record = StockOpname::create(['clinic_id' => $user->clinic_id, 'user_id' => $user->id, 'reason' => $reason]);
            StockOpnameItem::create(['clinic_id' => $user->clinic_id, 'stock_opname_id' => $record->id, 'medicine_batch_id' => $b->id, 'expected_quantity' => $b->quantity, 'counted_quantity' => $count]);
            $b->update(['quantity' => $count]);
            $this->movement($user, $b, $delta, $opname ? 'opname' : 'adjustment', 'opname:'.$record->id, $reason);
            Audit::record($user, $opname ? 'inventory.opname' : 'inventory.adjusted', $record, $reason);
        }, 3);
    }

    public function supplierReturn(User $user, MedicineBatch $batch, int $quantity, string $reason): void
    {
        Access::check($user, 'inventory.adjust', $batch);
        $this->validateReturn($quantity, $reason);
        DB::transaction(function () use ($user, $batch, $quantity, $reason) {
            $b = MedicineBatch::lockForUpdate()->findOrFail($batch->id);
            if ($quantity > $b->quantity) {
                throw ValidationException::withMessages(['quantity' => 'Retur melebihi saldo batch.']);
            }
            $r = StockReturn::create(['clinic_id' => $user->clinic_id, 'medicine_batch_id' => $b->id, 'user_id' => $user->id, 'quantity' => $quantity, 'kind' => 'supplier', 'reason' => $reason]);
            $b->decrement('quantity', $quantity);
            $this->movement($user, $b, -$quantity, 'supplier_return', 'return:'.$r->id, $reason);
            Audit::record($user, 'inventory.supplier-return', $r, $reason);
        }, 3);
    }

    public function patientReturn(User $user, DispenseAllocation $allocation, int $quantity, string $reason): StockReturn
    {
        Access::check($user, 'prescriptions.dispense', $allocation);
        $this->validateReturn($quantity, $reason);

        return DB::transaction(function () use ($user, $allocation, $quantity, $reason) {
            $a = DispenseAllocation::lockForUpdate()->findOrFail($allocation->id);
            if ($quantity > $a->quantity - $a->returned_quantity) {
                throw ValidationException::withMessages(['quantity' => 'Retur melebihi jumlah yang belum diretur.']);
            }
            $source = MedicineBatch::lockForUpdate()->findOrFail($a->medicine_batch_id);
            $quarantine = MedicineBatch::firstOrCreate(['clinic_id' => $user->clinic_id, 'medicine_id' => $source->medicine_id, 'batch_number' => $source->batch_number, 'condition' => 'quarantine'], ['expires_on' => $source->expires_on, 'purchase_price' => $source->purchase_price, 'quantity' => 0]);
            $quarantine->increment('quantity', $quantity);
            $a->increment('returned_quantity', $quantity);
            $r = StockReturn::create(['clinic_id' => $user->clinic_id, 'medicine_batch_id' => $quarantine->id, 'dispense_allocation_id' => $a->id, 'user_id' => $user->id, 'quantity' => $quantity, 'kind' => 'patient', 'reason' => $reason]);
            $this->movement($user, $quarantine, $quantity, 'patient_return', 'return:'.$r->id, $reason);
            Audit::record($user, 'inventory.patient-return', $r, $reason);

            return $r;
        }, 3);
    }

    private function validateReturn(int $quantity, string $reason): void
    {
        Validator::make(compact('quantity', 'reason'), ['quantity' => 'required|integer|min:1|max:1000000', 'reason' => 'required|string|min:5|max:1000'])->validate();
    }

    private function movement(User $user, MedicineBatch $b, int $quantity, string $kind, string $key, string $reason): void
    {
        StockMovement::create(['clinic_id' => $user->clinic_id, 'medicine_batch_id' => $b->id, 'user_id' => $user->id, 'quantity' => $quantity, 'kind' => $kind, 'source_key' => $key, 'reason' => $reason]);
    }
}
