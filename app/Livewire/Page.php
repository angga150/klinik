<?php

namespace App\Livewire;

use App\Support\Access;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
abstract class Page extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->active && auth()->user()->clinic?->active, 403);
    }

    protected function allowed(string $permission): void
    {
        Access::check(auth()->user(), $permission);
    }

    protected function clinic(): int
    {
        return auth()->user()->clinic_id;
    }

    protected function success(string $message = 'Perubahan berhasil disimpan.'): void
    {
        $this->resetErrorBag();
        session()->flash('success', $message);
    }
}
