<?php

use App\Actions\Billing\BillingWorkflow;
use App\Actions\Patient\SavePatient;
use App\Actions\Pharmacy\DispensePrescription;
use App\Actions\Visit\VisitWorkflow;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

// Test-only subprocess. It refuses any database not explicitly named *_test.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with(config('database.connections.mysql.database'), '_test')) {
    exit(90);
}
[$script,$mode,$payload,$barrier,$worker] = $argv;
$data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
file_put_contents($barrier.'.'.$worker, 'ready');
$deadline = microtime(true) + 30;
while (! file_exists($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        exit(91);
    }usleep(10000);
}
try {
    $user = User::where('email', $data['user'].'@klinik.test')->firstOrFail();
    if ($mode === 'patient') {
        $result = app(SavePatient::class)->execute($user, $data['input'])->id;
    } elseif ($mode === 'register') {
        $result = app(VisitWorkflow::class)->register($user, $data['input'])->id;
    } elseif ($mode === 'dispense') {
        app(DispensePrescription::class)->execute($user, Prescription::findOrFail($data['id']));
        $result = 1;
    } elseif ($mode === 'pay') {
        $result = app(BillingWorkflow::class)->pay($user, Invoice::findOrFail($data['id']), $data['input'])->id;
    } else {
        exit(92);
    }
    echo json_encode(['ok' => true, 'id' => $result]);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage());
    exit(1);
}
