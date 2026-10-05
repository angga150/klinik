<?php

namespace App\Services;

use App\Models\User;
use App\Support\Access;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportQuery
{
    public const TYPES = ['visits' => 'Kunjungan pasien', 'diagnoses' => 'Diagnosis terbanyak', 'procedures' => 'Tindakan medis', 'prescriptions' => 'Resep & penjualan obat', 'stock' => 'Posisi & nilai stok', 'movements' => 'Mutasi stok', 'expiry' => 'Obat kedaluwarsa', 'revenue' => 'Pendapatan bersih', 'methods' => 'Pembayaran per metode'];

    public function filters(array $input): array
    {
        return Validator::make($input, ['from' => 'required|date', 'to' => 'required|date|after_or_equal:from', 'polyclinic_id' => 'nullable|integer', 'medical_staff_id' => 'nullable|integer', 'status' => 'nullable|string|max:30'])->validate();
    }

    public function query(User $user, string $type, array $filters)
    {
        Access::check($user, 'reports.view');
        abort_unless(isset(self::TYPES[$type]), 422);
        $f = $this->filters($filters);
        $c = $user->clinic_id;
        $start = $f['from'].' 00:00:00';
        $end = $f['to'].' 23:59:59';
        $visits = DB::table('visits')->where('visits.clinic_id', $c)->whereBetween('visits.visit_date', [$f['from'], $f['to']])->when($f['polyclinic_id'] ?? null, fn ($q, $id) => $q->where('visits.polyclinic_id', $id))->when($f['medical_staff_id'] ?? null, fn ($q, $id) => $q->where('visits.medical_staff_id', $id))->when($f['status'] ?? null, fn ($q, $s) => $q->where('visits.status', $s));
        if ($type === 'visits') {
            return $visits->join('patients', 'patients.id', '=', 'visits.patient_id')->join('polyclinics', 'polyclinics.id', '=', 'visits.polyclinic_id')->join('medical_staff', 'medical_staff.id', '=', 'visits.medical_staff_id')->select('visits.number as Kunjungan', 'visits.visit_date as Tanggal', 'patients.medical_number as RM', 'polyclinics.name as Poli', 'medical_staff.name as Dokter', 'visits.status as Status')->selectRaw("CASE WHEN EXISTS (SELECT 1 FROM visits prior WHERE prior.patient_id=visits.patient_id AND prior.id<visits.id AND prior.clinic_id=visits.clinic_id) THEN 'Lama' ELSE 'Baru' END as Jenis")->orderByDesc('visits.id');
        }
        if ($type === 'diagnoses') {
            return $visits->join('medical_records', 'medical_records.visit_id', '=', 'visits.id')->whereIn('medical_records.status', ['finalized', 'amended'])->join('medical_record_diagnoses', 'medical_record_diagnoses.medical_record_id', '=', 'medical_records.id')->join('diagnoses', 'diagnoses.id', '=', 'medical_record_diagnoses.diagnosis_id')->select('diagnoses.code as Kode', 'diagnoses.name as Diagnosis')->selectRaw('COUNT(*) as Jumlah')->groupBy('diagnoses.code', 'diagnoses.name')->orderByDesc('Jumlah');
        }
        if ($type === 'procedures') {
            return $visits->join('medical_records', 'medical_records.visit_id', '=', 'visits.id')->whereIn('medical_records.status', ['finalized', 'amended'])->join('medical_record_procedures as p', 'p.medical_record_id', '=', 'medical_records.id')->select('p.description as Tindakan')->selectRaw('SUM(p.quantity) as Jumlah, SUM(p.price*p.quantity) as Nilai')->groupBy('p.description')->orderByDesc('Jumlah');
        }
        if ($type === 'prescriptions') {
            return $visits->join('prescriptions', 'prescriptions.visit_id', '=', 'visits.id')->where('prescriptions.status', 'dispensed')->join('prescription_items as p', 'p.prescription_id', '=', 'prescriptions.id')->join('medicines', 'medicines.id', '=', 'p.medicine_id')->select('medicines.sku as SKU', 'medicines.name as Obat')->selectRaw('SUM(p.quantity) as Diserahkan, SUM(COALESCE((SELECT SUM(a.returned_quantity) FROM dispense_allocations a WHERE a.prescription_item_id=p.id),0)) as Retur')->groupBy('medicines.sku', 'medicines.name')->orderByDesc('Diserahkan');
        }
        if (in_array($type, ['stock', 'expiry'])) {
            $q = DB::table('medicine_batches as b')->join('medicines as m', 'm.id', '=', 'b.medicine_id')->where('b.clinic_id', $c)->select('m.sku as SKU', 'm.name as Obat', 'b.batch_number as Batch', 'b.expires_on as Kedaluwarsa', 'b.condition as Kondisi', 'b.quantity as Saldo')->selectRaw('b.quantity*b.purchase_price as Nilai')->orderBy('b.expires_on');
            if ($type === 'expiry') {
                $q->whereDate('b.expires_on', '<=', $f['to']);
            }

            return $q;
        }
        if ($type === 'movements') {
            return DB::table('stock_movements as s')->join('medicine_batches as b', 'b.id', '=', 's.medicine_batch_id')->join('medicines as m', 'm.id', '=', 'b.medicine_id')->where('s.clinic_id', $c)->whereBetween('s.created_at', [$start, $end])->select('s.created_at as Tanggal', 'm.name as Obat', 'b.batch_number as Batch', 's.kind as Jenis', 's.quantity as Mutasi', 's.reason as Alasan')->orderByDesc('s.id');
        }
        $in = DB::table('payments as p')->join('payment_methods as m', 'm.id', '=', 'p.payment_method_id')->where('p.clinic_id', $c)->whereBetween('p.paid_at', [$start, $end])->selectRaw('DATE(p.paid_at) as tanggal, m.name as metode, p.amount as nilai');
        $out = DB::table('payment_voids as v')->join('payments as p', 'p.id', '=', 'v.payment_id')->join('payment_methods as m', 'm.id', '=', 'p.payment_method_id')->where('v.clinic_id', $c)->whereBetween('v.created_at', [$start, $end])->selectRaw('DATE(v.created_at) as tanggal, m.name as metode, -v.amount as nilai');
        $ledger = DB::query()->fromSub($in->unionAll($out), 'ledger');

        return $type === 'methods' ? $ledger->select('metode as Metode')->selectRaw('SUM(nilai) as Bersih')->groupBy('metode')->orderBy('metode') : $ledger->select('tanggal as Tanggal')->selectRaw('SUM(CASE WHEN nilai>=0 THEN nilai ELSE 0 END) as Masuk, SUM(CASE WHEN nilai<0 THEN -nilai ELSE 0 END) as Reversal, SUM(nilai) as Bersih')->groupBy('tanggal')->orderByDesc('tanggal');
    }
}
