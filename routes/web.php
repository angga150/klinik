<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Livewire\Clinical\VisitDetail;
use App\Livewire\Dashboard\Index;
use App\Livewire\Settings\Audit;
use App\Livewire\Settings\MasterData;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Users;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::view('/forgot-password', 'auth.forgot')->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token) => view('auth.reset', ['token' => $token]))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', Index::class)->name('dashboard');
    Route::get('/patients', App\Livewire\Patients\Index::class)->name('patients');
    Route::get('/visits', App\Livewire\Registration\Index::class)->name('visits');
    Route::get('/pharmacy', App\Livewire\Registration\Index::class)->defaults('area', 'pharmacy')->name('pharmacy');
    Route::get('/visits/{visit}', VisitDetail::class)->name('visits.show');
    Route::get('/inventory', App\Livewire\Inventory\Index::class)->name('inventory');
    Route::get('/billing', App\Livewire\Billing\Index::class)->name('billing');
    Route::get('/reports', App\Livewire\Reports\Index::class)->name('reports');
    Route::get('/settings/master/{resource?}', MasterData::class)->name('masters');
    Route::get('/settings/users', Users::class)->name('users');
    Route::get('/settings/profile', Profile::class)->name('profile');
    Route::get('/settings/audit', Audit::class)->name('audit');
    Route::get('/documents/registration/{visit}', [DocumentController::class, 'registration'])->name('print.registration');
    Route::get('/documents/receipt/{invoice}', [DocumentController::class, 'receipt'])->name('print.receipt');
    Route::get('/exports/{export}', [DocumentController::class, 'export'])->name('exports.download');
});
