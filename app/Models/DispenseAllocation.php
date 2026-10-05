<?php

namespace App\Models;

class DispenseAllocation extends ClinicModel
{
    protected $table = 'dispense_allocations';

    public function batch()
    {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }

    public function item()
    {
        return $this->belongsTo(PrescriptionItem::class, 'prescription_item_id');
    }
}
