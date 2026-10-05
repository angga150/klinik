<?php

namespace App\Models;

class ReportExport extends ClinicModel
{
    protected $table = 'report_exports';

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }
}
