<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\InventoryAlert;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Console\Command;

class RefreshInventoryAlerts extends Command
{
    protected $signature = 'clinic:inventory-alerts';

    protected $description = 'Refresh stock minimum and expiry alerts for every active clinic';

    public function handle(): int
    {
        foreach (Clinic::where('active', true)->get() as $clinic) {
            $keys = [];
            $medicines = Medicine::forClinic($clinic->id)->where('active', true)->withSum(['batches as available' => fn ($q) => $q->where('condition', 'usable')->whereDate('expires_on', '>', today())], 'quantity')->get();
            foreach ($medicines as $medicine) {
                if (($medicine->available ?? 0) <= $medicine->minimum_stock) {
                    $key = 'minimum:'.$medicine->id;
                    $keys[] = $key;
                    InventoryAlert::updateOrCreate(['clinic_id' => $clinic->id, 'key' => $key], ['message' => $medicine->name.': stok '.($medicine->available ?? 0).' dari minimum '.$medicine->minimum_stock, 'severity' => 'warning']);
                }
            }
            foreach (MedicineBatch::forClinic($clinic->id)->with('medicine')->where('quantity', '>', 0)->whereDate('expires_on', '<=', today()->addDays(90))->get() as $batch) {
                $key = 'expiry:'.$batch->id;
                $keys[] = $key;
                InventoryAlert::updateOrCreate(['clinic_id' => $clinic->id, 'key' => $key], ['message' => $batch->medicine->name.' / '.$batch->batch_number.' kedaluwarsa '.$batch->expires_on->format('d-m-Y'), 'severity' => $batch->expires_on->lte(today()) ? 'danger' : 'warning']);
            }
            InventoryAlert::forClinic($clinic->id)->whereNotIn('key', $keys)->delete();
        }
        $this->info('Inventory alerts updated.');

        return self::SUCCESS;
    }
}
