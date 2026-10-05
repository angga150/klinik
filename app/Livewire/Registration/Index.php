<?php

namespace App\Livewire\Registration;

use App\Actions\Visit\VisitWorkflow;
use App\Livewire\Page;
use App\Models\DoctorSchedule;
use App\Models\MedicalStaff;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\ServiceTariff;
use App\Models\Visit;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class Index extends Page
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Locked]
    public string $area = 'visits';

    public bool $showForm = false;

    public string $patientSearch = '';

    public array $form = ['payer' => 'Umum'];

    public function mount(string $area = 'visits'): void
    {
        $this->area = $area;
        $this->allowed($area === 'pharmacy' ? 'prescriptions.view' : 'visits.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->allowed('visits.register');
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function register(): void
    {
        $visit = app(VisitWorkflow::class)->register(auth()->user(), $this->form);
        $this->redirectRoute('visits.show', ['visit' => $visit->id], navigate: true);
    }

    public function call(int $id): void
    {
        app(VisitWorkflow::class)->transition(auth()->user(), Visit::forClinic($this->clinic())->findOrFail($id), 'called');
        $this->success('Pasien telah dipanggil.');
    }

    public function render()
    {
        $this->allowed($this->area === 'pharmacy' ? 'prescriptions.view' : 'visits.view');
        $q = Visit::forClinic($this->clinic())->with(['patient:id,name,medical_number', 'polyclinic', 'doctor', 'queue']);
        if ($this->area === 'pharmacy') {
            $q->whereHas('prescription', fn ($p) => $p->whereIn('status', ['submitted', 'processing', 'ready', 'dispensed']));
        }
        $q->when($this->status, fn ($q) => $q->where('status', $this->status))->when($this->search, fn ($q) => $q->whereHas('patient', fn ($p) => $p->where('name', 'like', '%'.$this->search.'%')->orWhere('medical_number', 'like', '%'.$this->search.'%')));

        return view('livewire.registration.index', ['rows' => $q->latest('id')->paginate(15), 'patients' => $this->showForm ? Patient::forClinic($this->clinic())->where('active', true)->where(fn ($p) => $p->where('name', 'like', '%'.$this->patientSearch.'%')->orWhere('medical_number', 'like', '%'.$this->patientSearch.'%'))->limit(30)->get(['id', 'name', 'medical_number']) : collect(), 'polys' => Polyclinic::forClinic($this->clinic())->where('active', true)->get(), 'doctors' => MedicalStaff::forClinic($this->clinic())->where('active', true)->where('profession', 'Dokter')->get(), 'tariffs' => ServiceTariff::forClinic($this->clinic())->where('active', true)->where('kind', 'consultation')->get(), 'schedules' => DoctorSchedule::forClinic($this->clinic())->with(['staff', 'polyclinic'])->where('weekday', now()->dayOfWeek)->where('active', true)->get()])->title($this->area === 'pharmacy' ? 'Pelayanan apotek' : 'Kunjungan & antrean');
    }
}
