<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ReportExport;
use App\Models\Visit;
use App\Support\Access;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function registration(Visit $visit)
    {
        Access::check(auth()->user(), 'visits.view', $visit);
        $visit->load(['patient', 'polyclinic', 'doctor', 'queue']);

        return Pdf::loadView('print.registration', ['visit' => $visit, 'clinic' => auth()->user()->clinic])->setPaper('a5')->stream('pendaftaran-'.$visit->number.'.pdf');
    }

    public function receipt(Invoice $invoice)
    {
        Access::check(auth()->user(), 'invoices.view', $invoice);
        $invoice->load(['visit.patient', 'items', 'payments.method', 'payments.void']);

        return Pdf::loadView('print.receipt', ['invoice' => $invoice, 'clinic' => auth()->user()->clinic])->setPaper('a5')->stream('kuitansi-'.$invoice->number.'.pdf');
    }

    public function export(ReportExport $export)
    {
        Access::check(auth()->user(), 'reports.view', $export);
        abort_unless($export->user_id === auth()->id() && $export->status === 'completed' && $export->path, 403);
        abort_unless(Storage::disk('local')->exists($export->path), 404);

        return Storage::disk('local')->download($export->path, 'laporan-'.$export->report.'.'.$export->format);
    }
}
