<?php

namespace App\Livewire\Settings;

use App\Livewire\Page;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use Livewire\WithPagination;

class Audit extends Page
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->allowed('audit.view');

        return view('livewire.settings.audit', ['rows' => AuditLog::forClinic($this->clinic())->with('actor')->where('action', 'like', '%'.$this->search.'%')->latest('id')->paginate(20), 'logins' => LoginHistory::forClinic($this->clinic())->with('user')->latest('id')->limit(10)->get()])->title('Audit & aktivitas');
    }
}
