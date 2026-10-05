<?php

namespace App\Console\Commands;

use App\Services\DemoBackup;
use Illuminate\Console\Command;

class RestoreDemo extends Command
{
    protected $signature = 'clinic:restore {path : Relative private backup path} {--force : Confirm replacement of demo database contents}';

    protected $description = 'Restore an encrypted demo snapshot into a migrated database';

    public function handle(DemoBackup $backup): int
    {
        if (! app()->environment(['local', 'testing']) || (! $this->option('force')) || (! app()->environment('testing') && ! app()->isDownForMaintenance())) {
            $this->error('Local demo only. Stop workers, run artisan down, and supply --force to replace data.');

            return self::FAILURE;
        }
        $backup->restore($this->argument('path'));
        $this->info('Demo restored. Run artisan up.');

        return self::SUCCESS;
    }
}
