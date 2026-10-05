<?php

namespace Tests\Feature;

use App\Actions\Billing\BillingWorkflow;
use App\Jobs\GenerateReport;
use App\Models\Clinic;
use App\Models\ReportExport;
use App\Services\ReportQuery;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReportsTest extends ClinicTestCase
{
    private function filters(): array
    {
        return ['from' => today()->startOfMonth()->toDateString(), 'to' => today()->toDateString()];
    }

    public function test_all_report_queries_and_pdf_excel_exports(): void
    {
        Storage::fake('local');
        $v = $this->clinical(false);
        $i = $v->invoices()->first();
        $b = app(BillingWorkflow::class);
        $b->issue($this->user('kasir'), $i);
        $b->pay($this->user('kasir'), $i, $this->paymentData());
        $q = app(ReportQuery::class);
        foreach (array_keys(ReportQuery::TYPES) as $type) {
            $this->assertNotNull($q->query($this->user('kepala'), $type, $this->filters())->get());
        }
        foreach (['pdf', 'xlsx'] as $format) {
            $e = ReportExport::create(['clinic_id' => $this->user()->clinic_id, 'user_id' => $this->user()->id, 'report' => 'visits', 'format' => $format, 'filters' => $this->filters()]);
            (new GenerateReport($e->id))->handle($q);
            $e->refresh();
            $this->assertSame('completed', $e->status);
            Storage::disk('local')->assertExists($e->path);
            if ($format === 'pdf') {
                $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($e->path));
            } else {
                $book = IOFactory::load(Storage::disk('local')->path($e->path));
                $this->assertSame('Kunjungan', $book->getActiveSheet()->getCell('A1')->getValue());
                $this->assertSame($v->number, $book->getActiveSheet()->getCell('A2')->getValue());
                $book->disconnectWorksheets();
            }$this->actingAs($this->user())->get('/exports/'.$e->id)->assertOk();
            $this->actingAs($this->user('kepala'))->get('/exports/'.$e->id)->assertForbidden();
        }
    }

    public function test_revenue_subtracts_reversals_on_reversal_date(): void
    {
        $v = $this->clinical(false);
        $i = $v->invoices()->first();
        $b = app(BillingWorkflow::class);
        $b->issue($this->user('kasir'), $i);
        $p = $b->pay($this->user('kasir'), $i, $this->paymentData());
        $b->voidPayment($this->user('admin'), $p, 'Koreksi pembayaran uji');
        $rows = app(ReportQuery::class)->query($this->user('kepala'), 'revenue', $this->filters())->get();
        $this->assertEquals('0.00', $rows->sum('Bersih'));
    }

    public function test_reports_scope_clinic_and_print_documents(): void
    {
        $v = $this->visit();
        $this->actingAs($this->user('pendaftaran'))->get('/documents/registration/'.$v->id)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->user('kasir'))->get('/documents/receipt/'.$v->invoices()->first()->id)->assertOk();
        $u = $this->user('kepala');
        $u->clinic_id = Clinic::create(['name' => 'Klinik lain'])->id;
        $u->save();
        $this->assertSame(0, app(ReportQuery::class)->query($u, 'visits', $this->filters())->count());
    }
}
