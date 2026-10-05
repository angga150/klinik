<?php

namespace App\Livewire\Patients;

use App\Actions\Patient\SavePatient;
use App\Livewire\Page;
use App\Models\Patient;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class Index extends Page
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Locked]
    public ?int $editing = null;

    public bool $showForm = false;

    public array $form = [];

    public function mount(): void
    {
        $this->allowed('patients.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->allowed('patients.create');
        $this->editing = null;
        $this->form = ['sex' => 'L', 'payer' => 'Umum', 'active' => true];
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        $this->allowed('patients.update');
        $p = Patient::forClinic($this->clinic())->findOrFail($id);
        $this->editing = $id;
        $this->form = $p->only(['name', 'nik', 'birth_place', 'sex', 'phone', 'address', 'blood_type', 'emergency_contact', 'payer', 'active']);
        $this->form['birth_date'] = $p->birth_date->format('Y-m-d');
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $p = $this->editing ? Patient::forClinic($this->clinic())->findOrFail($this->editing) : null;
        app(SavePatient::class)->execute(auth()->user(), $this->form, $p);
        $this->showForm = false;
        $this->success('Data pasien tersimpan. Nomor rekam medis siap digunakan.');
    }

    public function render()
    {
        $this->allowed('patients.view');
        $q = Patient::forClinic($this->clinic())->where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('medical_number', 'like', '%'.$this->search.'%')->orWhere('nik', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%'));
        $duplicates = collect();
        if ($this->showForm && strlen($this->form['name'] ?? '') >= 3) {
            $duplicates = Patient::forClinic($this->clinic())->when($this->editing, fn ($q) => $q->where('id', '!=', $this->editing))->where(function ($q) {
                $q->where('name', 'like', '%'.$this->form['name'].'%');
                if (filled($this->form['phone'] ?? null)) {
                    $q->orWhere('phone', $this->form['phone']);
                }
            })->limit(5)->get(['id', 'name', 'medical_number']);
        }

        return view('livewire.patients.index', ['rows' => $q->latest('id')->paginate(15), 'duplicates' => $duplicates])->title('Data pasien');
    }
}
