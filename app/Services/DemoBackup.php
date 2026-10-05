<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

class DemoBackup
{
    private function tables(): array
    {
        return array_values(array_filter(Schema::getTableListing(), fn ($t) => ! in_array($t, ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'job_batches', 'password_reset_tokens'])));
    }

    public function create(): string
    {
        $tables = $this->tables();
        $data = DB::transaction(function () use ($tables) {
            $snapshot = [];
            foreach ($tables as $table) {
                $snapshot[$table] = DB::table($table)->get()->map(fn ($r) => (array) $r)->all();
            }

            return ['version' => 1, 'created_at' => now()->toIso8601String(), 'tables' => $snapshot];
        });
        $path = 'backups/clinic-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.enc';
        Storage::disk('local')->put($path, Crypt::encryptString(json_encode($data, JSON_THROW_ON_ERROR)));

        return $path;
    }

    public function restore(string $path): void
    {
        if (! str_starts_with($path, 'backups/') || str_contains($path, '..')) {
            throw new \InvalidArgumentException('Use a backup path inside private backups/.');
        }
        $data = json_decode(Crypt::decryptString(Storage::disk('local')->get($path)), true, flags: JSON_THROW_ON_ERROR);
        $tables = $this->tables();
        $incoming = array_keys($data['tables'] ?? []);
        sort($tables);
        sort($incoming);
        if (($data['version'] ?? null) !== 1 || $incoming !== $tables) {
            throw new \RuntimeException('Backup schema differs from the migrated database.');
        }
        // Demo restores require maintenance mode and stopped workers. No DDL/TRUNCATE: rollback stays available.
        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($data, $tables) {
                foreach ($tables as $table) {
                    DB::table($table)->delete();
                }foreach ($tables as $table) {
                    foreach (array_chunk($data['tables'][$table], 200) as $rows) {
                        DB::table($table)->insert($rows);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
        DB::table('sessions')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
