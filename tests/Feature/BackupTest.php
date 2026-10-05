<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Services\DemoBackup;
use Illuminate\Support\Facades\Storage;

class BackupTest extends ClinicTestCase
{
    public function test_encrypted_snapshot_restores_demo_data(): void
    {
        Storage::fake('local');
        $p = $this->patient();
        $backup = app(DemoBackup::class);
        $path = $backup->create();
        $this->assertStringNotContainsString($p->name, Storage::disk('local')->get($path));
        $p->update(['name' => 'Changed']);
        $backup->restore($path);
        $this->assertSame('Pasien Uji', Patient::find($p->id)->name);
    }
}
