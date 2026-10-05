<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Page;
use App\Models\AuditLog;
use App\Models\DoctorSchedule;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Payment;
use App\Models\PaymentVoid;
use App\Models\Prescription;
use App\Models\QueueEntry;
use App\Models\Visit;
use App\Support\Money;
use Carbon\Carbon;

class Index extends Page
{
    public function render()
    {
        $this->allowed('dashboard.view');
        $c = $this->clinic();
        $u = auth()->user();
        $base = Visit::forClinic($c)->whereDate('visit_date', today());
        $metrics = ['today' => (clone $base)->count(), 'waiting' => (clone $base)->whereIn('status', ['waiting_triage', 'waiting_doctor'])->count(), 'consulting' => (clone $base)->where('status', 'in_consultation')->count(), 'completed' => (clone $base)->where('status', 'completed')->count(), 'pharmacy' => Prescription::forClinic($c)->whereIn('status', ['submitted', 'processing', 'ready'])->count()];
        $financial = $u->can('invoices.view') || $u->can('reports.view');
        $metrics['revenue'] = $financial ? Money::sub((string) Payment::forClinic($c)->whereDate('paid_at', today())->sum('amount'), (string) PaymentVoid::forClinic($c)->whereDate('created_at', today())->sum('amount')) : null;
        $dates = Visit::forClinic($c)->whereDate('visit_date', '>=', today()->subDays(6))->selectRaw('visit_date, COUNT(*) as count')->groupBy('visit_date')->pluck('count', 'visit_date');
        $series = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $series[] = ['label' => $date->translatedFormat('D'), 'count' => $dates[$date->format('Y-m-d')] ?? 0];
        }
        $low = ($u->can('inventory.view') || $u->can('reports.view')) ? Medicine::forClinic($c)->where('active', true)->withSum(['batches as available' => fn ($q) => $q->where('condition', 'usable')->whereDate('expires_on', '>', today())], 'quantity')->get()->filter(fn ($m) => ($m->available ?? 0) <= $m->minimum_stock)->take(5) : collect();
        $expiry = ($u->can('inventory.view') || $u->can('reports.view')) ? MedicineBatch::forClinic($c)->with('medicine')->where('quantity', '>', 0)->whereDate('expires_on', '<=', today()->addDays(90))->orderBy('expires_on')->limit(4)->get() : collect();
        $waits = QueueEntry::forClinic($c)->whereDate('queue_date', today())->whereNotNull('consultation_at')->get(['created_at', 'consultation_at']);
        $average = $waits->count() ? round($waits->avg(fn ($q) => $q->created_at->diffInMinutes(Carbon::parse($q->consultation_at)))) : 0;

        return view('livewire.dashboard.index', ['metrics' => $metrics, 'series' => $series, 'lowStock' => $low, 'expiry' => $expiry, 'average' => $average, 'visits' => $u->can('visits.view') ? (clone $base)->with(['patient:id,name,medical_number', 'polyclinic', 'queue'])->latest('id')->limit(5)->get() : collect(), 'activity' => $u->can('audit.view') ? AuditLog::forClinic($c)->with('actor')->latest('id')->limit(5)->get() : collect(), 'schedules' => DoctorSchedule::forClinic($c)->with(['staff', 'polyclinic'])->where('weekday', now()->dayOfWeek)->where('active', true)->limit(4)->get()])->title('Pusat kendali');
    }
}
