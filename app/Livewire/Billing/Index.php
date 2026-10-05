<?php

namespace App\Livewire\Billing;

use App\Livewire\Page;
use App\Models\Invoice;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class Index extends Page
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->allowed('invoices.view');

        return view('livewire.billing.index', ['rows' => Invoice::forClinic($this->clinic())->with('visit.patient')->withSum('items', 'total')->when($this->status, fn ($q) => $q->where('status', $this->status))->where(fn ($q) => $q->where('number', 'like', '%'.$this->search.'%')->orWhereHas('visit.patient', fn ($p) => $p->where('name', 'like', '%'.$this->search.'%')))->latest('id')->paginate(15)])->title('Billing & kasir');
    }
}
