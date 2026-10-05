<?php

namespace App\Livewire\Inventory;

use App\Actions\Inventory\InventoryWorkflow;
use App\Livewire\Page;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class Index extends Page
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = '';

    #[Locked]
    public ?int $batchId = null;

    public string $mode = '';

    public array $form = [];

    public string $reason = '';

    public int $quantity = 0;

    public function mount(): void
    {
        $this->allowed('inventory.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function receive(): void
    {
        $this->allowed('inventory.receive');
        $this->mode = 'receive';
        $this->form = [];
        $this->resetErrorBag();
    }

    public function selectBatch(int $id, string $mode): void
    {
        $this->allowed('inventory.view');
        abort_unless(in_array($mode, ['ledger', 'adjustment', 'opname', 'return']), 422);
        $b = MedicineBatch::forClinic($this->clinic())->findOrFail($id);
        $this->batchId = $id;
        $this->mode = $mode;
        $this->quantity = $mode === 'return' ? 1 : $b->quantity;
        $this->reason = '';
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $action = app(InventoryWorkflow::class);
        $u = auth()->user();
        if ($this->mode === 'receive') {
            $action->receive($u, $this->form);
        } else {
            $b = MedicineBatch::forClinic($this->clinic())->findOrFail($this->batchId);
            if ($this->mode === 'return') {
                $action->supplierReturn($u, $b, $this->quantity, $this->reason);
            } elseif (in_array($this->mode, ['adjustment', 'opname'])) {
                $action->adjust($u, $b, $this->quantity, $this->reason, $this->mode === 'opname');
            } else {
                abort(422);
            }
        }
        $this->mode = '';
        $this->success('Transaksi stok dan ledger tersimpan.');
    }

    public function render()
    {
        $this->allowed('inventory.view');
        $q = MedicineBatch::forClinic($this->clinic())->with('medicine.unit')->whereHas('medicine', fn ($m) => $m->where('name', 'like', '%'.$this->search.'%')->orWhere('sku', 'like', '%'.$this->search.'%'));
        if ($this->filter === 'expired') {
            $q->whereDate('expires_on', '<=', today());
        } elseif (in_array($this->filter, ['30', '60', '90'])) {
            $q->whereDate('expires_on', '>', today())->whereDate('expires_on', '<=', today()->addDays((int) $this->filter));
        } elseif ($this->filter === 'quarantine') {
            $q->where('condition', 'quarantine');
        }

        return view('livewire.inventory.index', ['rows' => $q->orderBy('expires_on')->paginate(15), 'medicines' => Medicine::forClinic($this->clinic())->where('active', true)->get(), 'suppliers' => Supplier::forClinic($this->clinic())->where('active', true)->get(), 'ledger' => $this->mode === 'ledger' ? StockMovement::forClinic($this->clinic())->where('medicine_batch_id', $this->batchId)->with('actor')->latest('id')->paginate(10, pageName: 'ledgerPage') : null, 'value' => MedicineBatch::forClinic($this->clinic())->where('condition', 'usable')->whereDate('expires_on', '>', today())->selectRaw('COALESCE(SUM(quantity * purchase_price),0) as total')->value('total')])->title('Persediaan obat');
    }
}
