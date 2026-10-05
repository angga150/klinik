<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Models\User;
use App\Services\ReportQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GenerateReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public int $exportId) {}

    public function handle(ReportQuery $reports): void
    {
        $e = ReportExport::findOrFail($this->exportId);
        if ($e->status === 'completed') {
            return;
        }$user = User::findOrFail($e->user_id);
        abort_unless($user->clinic_id === $e->clinic_id, 403);
        $query = $reports->query($user, $e->report, $e->filters);
        $e->update(['status' => 'processing', 'error' => null]);
        Storage::disk('local')->makeDirectory('exports');
        $path = 'exports/'.$e->id.'.'.$e->format;
        if ($e->format === 'pdf') {
            $rows = $query->get();
            Storage::disk('local')->put($path, Pdf::loadView('print.report', ['title' => ReportQuery::TYPES[$e->report], 'rows' => $rows, 'filters' => $e->filters, 'clinic' => $user->clinic])->setPaper('a4', 'landscape')->output());
        } else {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Laporan');
            $rowNo = 1;
            foreach ($query->cursor() as $row) {
                $values = (array) $row;
                if ($rowNo === 1) {
                    foreach (array_keys($values) as $i => $label) {
                        $sheet->setCellValue([$i + 1, $rowNo], $label);
                    }$sheet->getStyle('1:1')->getFont()->setBold(true);
                    $rowNo++;
                }
                foreach (array_values($values) as $i => $value) {
                    $sheet->setCellValueExplicit([$i + 1, $rowNo], (string) $value, DataType::TYPE_STRING);
                }$rowNo++;
            }
            $sheet->freezePane('A2');
            foreach (range('A', $sheet->getHighestColumn()) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            (new Xlsx($book))->save(Storage::disk('local')->path($path));
            $book->disconnectWorksheets();
        }
        $e->update(['path' => $path, 'status' => 'completed']);
    }

    public function failed(?\Throwable $error): void
    {
        ReportExport::whereKey($this->exportId)->update(['status' => 'failed', 'error' => 'Ekspor gagal. Periksa worker dan jalankan ekspor kembali.']);
    }
}
