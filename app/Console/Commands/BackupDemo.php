<?php

namespace App\Console\Commands;

use App\Services\DemoBackup;
use Illuminate\Console\Command;

class BackupDemo extends Command
{
    protected $signature = 'clinic:backup';

    protected $description = 'Create an encrypted local demo database snapshot';

    public function handle(DemoBackup $backup): int
    {
        $this->info($backup->create());

        return self::SUCCESS;
    }
}
