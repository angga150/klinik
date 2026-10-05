<?php

namespace App\Livewire\Reports;

use App\Jobs\GenerateReport;
use App\Livewire\Page;
use App\Models\MedicalStaff;
use App\Models\Polyclinic;
use App\Models\ReportExport;
use App\Services\ReportQuery;
use App\Support\Audit;
use Livewire\WithPagination;

class Index extends Page
{
    use WithPagination;

    public string $report = 'visits';

    public array $filters = [];

    public function mount(): void
    {
        $this->allowed('reports.view');
        $this->filters = ['from' => today()->startOfMonth()->toDateString(), 'to' => today()->toDateString(), 'polyclinic_id' => '', 'medical_staff_id' => '', 'status' => ''];
    }

    public function updatedReport(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function export(string $format): void
    {
        $this->allowed('reports.view');
        abort_unless(in_array($format, ['pdf', 'xlsx']), 422);
        $filters = app(ReportQuery::class)->filters($this->filters);
        abort_unless(isset(ReportQuery::TYPES[$this->report]), 422);
        $e = ReportExport::create(['clinic_id' => $this->clinic(), 'user_id' => auth()->id(), 'report' => $this->report, 'format' => $format, 'filters' => $filters]);
        GenerateReport::dispatch($e->id);
        Audit::record(auth()->user(), 'report.requested', $e);
        $this->success('Ekspor masuk antrean. Tautan unduhan muncul setelah selesai.');
    }

    public function render()
    {
        $this->allowed('reports.view');

        return view('livewire.reports.index', ['types' => ReportQuery::TYPES, 'rows' => app(ReportQuery::class)->query(auth()->user(), $this->report, $this->filters)->paginate(20), 'exports' => ReportExport::forClinic($this->clinic())->where('user_id', auth()->id())->latest('id')->limit(10)->get(), 'polys' => Polyclinic::forClinic($this->clinic())->get(), 'doctors' => MedicalStaff::forClinic($this->clinic())->where('profession', 'Dokter')->get()])->title('Laporan & analitik');
    }
}
