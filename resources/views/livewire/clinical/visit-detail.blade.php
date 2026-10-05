<div><div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div><a href="{{ route('visits') }}" wire:navigate class="link text-xs">← Kunjungan & antrean</a><h1 class="page-title mt-2">{{ $visit->patient->name }}</h1><p class="subtitle">{{ $visit->patient->medical_number }} · {{ $visit->patient->sex==='L'?'Laki-laki':'Perempuan' }} · {{ $visit->patient->birth_date->age }} tahun · {{ $visit->number }}</p></div><div class="flex items-center gap-3"><x-ui.badge :status="$visit->status"/><a href="{{ route('print.registration',$visit) }}" target="_blank" class="btn-secondary">Bukti pendaftaran</a></div></div><x-ui.feedback/>
<div class="card mb-6 grid gap-4 p-5 md:grid-cols-4">
@foreach(['Poli'=>$visit->polyclinic->name,'Dokter'=>$visit->doctor->name,'Tanggal'=>$visit->visit_date->format('d M Y'),'Antrean'=>str_pad($visit->queue->number,3,'0',STR_PAD_LEFT)] as $label=>$value)<div><p class="eyebrow">{{ $label }}</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ $value }}</p></div>
@endforeach</div>
@can('queues.manage')
@if(in_array($visit->status,['waiting_triage','waiting_doctor']))<section class="card mb-6 p-5"><div class="flex flex-wrap gap-3"><button wire:click="queue('called')" class="btn">Panggil pasien</button><input wire:model="reason" class="field max-w-sm" placeholder="Alasan pembatalan / tidak hadir" aria-label="Alasan antrean"><button wire:click="queue('cancelled')" wire:confirm="Batalkan kunjungan ini?" class="btn-danger">Batalkan</button><button wire:click="queue('no_show')" wire:confirm="Tandai pasien tidak hadir?" class="btn-secondary">Tidak hadir</button></div></section>
@endif
@endcan
@if(auth()->user()->can('vitals.create') || auth()->user()->can('medical-records.view'))<section class="card mb-6"><div class="card-header"><h2 class="font-semibold">01 · Pemeriksaan awal</h2><span class="text-xs text-slate-400">Tanda vital & keluhan</span></div><div class="p-5">
@if($visit->vitals)<div class="grid gap-4 md:grid-cols-4">
@foreach(['Tekanan darah'=>$visit->vitals->systolic.'/'.$visit->vitals->diastolic.' mmHg','Suhu'=>$visit->vitals->temperature.' °C','Nadi'=>$visit->vitals->pulse.' /menit','SpO₂'=>$visit->vitals->oxygen.'%','Berat / tinggi'=>$visit->vitals->weight.' kg / '.$visit->vitals->height.' cm','BMI'=>$visit->vitals->bmi,'Respirasi'=>$visit->vitals->respiration.' /menit','Alergi'=>$visit->vitals->allergies?:'Tidak tercatat'] as $label=>$value)<div><p class="label">{{ $label }}</p><p class="text-sm">{{ $value }}</p></div>
@endforeach</div><p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm">{{ $visit->vitals->complaint }}</p>
@elseif($visit->status==='waiting_triage' && auth()->user()->can('vitals.create'))<form wire:submit="triage"><div class="form-grid"><x-ui.field label="Keluhan utama *" model="vitals.complaint" type="textarea"/><x-ui.field label="Alergi" model="vitals.allergies" type="textarea"/>
@foreach(['systolic'=>'Sistolik (mmHg)','diastolic'=>'Diastolik (mmHg)','temperature'=>'Suhu (°C)','weight'=>'Berat (kg)','height'=>'Tinggi (cm)','pulse'=>'Nadi /menit','respiration'=>'Respirasi /menit','oxygen'=>'Saturasi O₂ (%)'] as $key=>$label)<x-ui.field :label="$label" :model="'vitals.'.$key" type="number" step="any"/>
@endforeach<x-ui.field label="Catatan perawat" model="vitals.notes" type="textarea"/></div><button class="btn mt-5" wire:loading.attr="disabled">Simpan & kirim ke dokter</button></form>
@else<x-ui.empty>Pemeriksaan awal belum tersedia.</x-ui.empty>
@endif</div></section>
@endif
@can('medical-records.view')<section class="card mb-6"><div class="card-header"><h2 class="font-semibold">02 · Pemeriksaan dokter</h2>
@if($visit->record)<x-ui.badge :status="$visit->record->status"/>
@endif</div><div class="p-5">
@if($visit->status==='waiting_doctor')<button wire:click="start" class="btn">Mulai pemeriksaan</button>
@elseif($visit->status==='in_consultation')<form wire:submit="saveClinical(false)"><div class="form-grid">
@foreach(['subjective'=>'S · Subjective / anamnesis','objective'=>'O · Objective','assessment'=>'A · Assessment','plan'=>'P · Plan','history'=>'Riwayat penyakit','physical_exam'=>'Pemeriksaan fisik','advice'=>'Saran dokter'] as $key=>$label)<x-ui.field :label="$label" :model="'clinical.'.$key" type="textarea"/>
@endforeach<x-ui.field label="Tanggal kontrol" model="clinical.control_date" type="date"/><label><span class="label">Diagnosis (pilihan pertama = utama)</span><select class="field h-36" wire:model="clinical.diagnosis_ids" multiple>
@foreach($diagnoses as $d)<option value="{{ $d->id }}">{{ $d->code }} · {{ $d->name }}</option>
@endforeach</select><span class="text-[10px] text-slate-400">Ctrl/Cmd untuk memilih lebih dari satu.</span></label><label><span class="label">Tindakan medis</span><select class="field h-36" wire:model="clinical.procedure_ids" multiple>
@foreach($procedures as $p)<option value="{{ $p->id }}">{{ $p->name }} · {{ \App\Support\Money::format($p->price) }}</option>
@endforeach</select></label></div><div class="mt-6 border-t border-slate-100 pt-5"><div class="mb-4 flex justify-between"><h3 class="font-semibold">Resep elektronik</h3><button type="button" wire:click="addMedicine" class="btn-secondary text-xs">+ Tambah obat</button></div>
@foreach($clinical['medicines'] as $index=>$item)<div wire:key="medicine-{{ $index }}" class="mb-4 rounded-xl bg-slate-50 p-4"><div class="form-grid"><label><span class="label">Obat</span><select class="field" wire:model="clinical.medicines.{{ $index }}.medicine_id"><option value="">Pilih obat…</option>
@foreach($medicines as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>
@endforeach</select></label>
@foreach(['quantity'=>'Jumlah','dose'=>'Dosis','frequency'=>'Frekuensi','duration'=>'Durasi','instructions'=>'Cara pemakaian'] as $key=>$label)<x-ui.field :label="$label" :model="'clinical.medicines.'.$index.'.'.$key" :type="$key==='quantity'?'number':'text'"/>
@endforeach</div><button type="button" wire:click="removeMedicine({{ $index }})" class="mt-3 text-xs text-red-600">Hapus baris resep</button></div>
@endforeach</div><div class="mt-5 flex justify-end gap-3"><button class="btn-secondary" wire:loading.attr="disabled">Simpan draft</button><button type="button" class="btn" wire:click="saveClinical(true)" wire:confirm="Finalisasi akan mengunci catatan medis dan mengirim resep. Lanjutkan?" wire:loading.attr="disabled">Finalisasi pemeriksaan</button></div></form>
@elseif($visit->record)<div class="form-grid">
@foreach(['subjective'=>'Subjective','objective'=>'Objective','assessment'=>'Assessment','plan'=>'Plan','history'=>'Riwayat','physical_exam'=>'Pemeriksaan fisik','advice'=>'Saran'] as $key=>$label)<div><p class="label">{{ $label }}</p><p class="whitespace-pre-wrap text-sm leading-relaxed">{{ $visit->record->$key?:'—' }}</p></div>
@endforeach</div><div class="mt-4 text-sm">
@foreach($visit->record->diagnoses as $diagnosis)<span class="mr-2 inline-block rounded bg-teal-50 px-2 py-1 text-xs text-teal-700">{{ $diagnosis->diagnosis->code }} · {{ $diagnosis->diagnosis->name }} {{ $diagnosis->primary?'(utama)':'' }}</span>
@endforeach</div><p class="mt-4 text-xs text-slate-400">Kontrol: {{ $visit->record->control_date?->format('d M Y') ?? 'Tidak dijadwalkan' }}</p>
@foreach($visit->record->amendments as $a)<div class="mt-4 rounded-lg border-l-4 border-amber-300 bg-amber-50 p-4 text-sm"><p class="font-semibold">Amendment · {{ $a->author->name }} · {{ $a->created_at->format('d/m/Y H:i') }}</p><p class="mt-2">{{ $a->reason }}</p><p class="mt-2 whitespace-pre-wrap">{{ $a->content }}</p></div>
@endforeach 
@can('medical-records.write')<details class="mt-5 border-t border-slate-100 pt-4"><summary class="cursor-pointer text-sm font-semibold text-teal-700">Tambahkan amendment</summary><form wire:submit="amend" class="mt-4 space-y-3"><x-ui.field label="Alasan koreksi" model="reason"/><x-ui.field label="Catatan koreksi (catatan asli dipertahankan)" model="amendment" type="textarea"/><button class="btn" wire:loading.attr="disabled">Simpan amendment</button></form></details>
@endcan 
@else<x-ui.empty>Menunggu pemeriksaan awal selesai.</x-ui.empty>
@endif</div></section>
@if($history->count())<details class="card mb-6 p-5"><summary class="cursor-pointer font-semibold">Riwayat pelayanan sebelumnya (10 terakhir)</summary>
@foreach($history as $h)<div class="mt-4 border-t border-slate-100 pt-3 text-sm"><a class="link" href="{{ route('visits.show',$h) }}" wire:navigate>{{ $h->visit_date->format('d M Y') }} · {{ $h->doctor->name }}</a><p class="mt-1">{{ $h->record?->assessment??'Belum ada catatan medis' }}</p></div>
@endforeach</details>
@endif
@endcan
@can('prescriptions.view')
@if($visit->prescription && $visit->prescription->status!=='draft')<section class="card mb-6"><div class="card-header"><h2 class="font-semibold">03 · Resep & penyerahan obat</h2><x-ui.badge :status="$visit->prescription->status"/></div><p class="mx-5 mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Alergi tercatat: {{ $visit->vitals?->allergies ?: 'Belum tercatat; konfirmasi kepada pasien.' }}</p><div class="table-wrap"><table class="data-table"><thead><tr><th>Obat</th><th>Jumlah</th><th>Aturan pakai</th><th>Batch penyerahan</th></tr></thead><tbody>
@foreach($visit->prescription->items as $item)<tr><td class="font-semibold">{{ $item->medicine->name }}</td><td>{{ $item->quantity }} {{ $item->unit }}</td><td>{{ $item->dose }} · {{ $item->frequency }} · {{ $item->duration }}<p class="mt-1 text-xs text-slate-400">{{ $item->instructions }}</p></td><td>
@foreach($item->allocations as $a)<p class="text-xs">#{{ $a->id }} · {{ $a->batch->batch_number }}: {{ $a->quantity }} (retur {{ $a->returned_quantity }})</p>
@endforeach</td></tr>
@endforeach</tbody></table></div>
@can('prescriptions.dispense')<div class="flex flex-wrap gap-3 border-t border-slate-100 p-5">
@if($visit->prescription->status==='submitted')<button wire:click="pharmacy('processing')" class="btn">Verifikasi & proses resep</button>
@elseif($visit->prescription->status==='processing')<button wire:click="pharmacy('ready')" class="btn">Tandai obat siap</button>
@elseif($visit->prescription->status==='ready')<button wire:click="pharmacy('dispensed')" wire:confirm="Konfirmasi obat benar-benar diserahkan kepada pasien?" wire:loading.attr="disabled" class="btn">Serahkan obat · FEFO</button>
@endif 
@if(in_array($visit->prescription->status,['submitted','processing','ready']))<input class="field max-w-xs" wire:model="reason" placeholder="Alasan pembatalan resep" aria-label="Alasan pembatalan resep"><button wire:click="pharmacy('cancelled')" wire:confirm="Batalkan seluruh resep ini?" class="btn-danger">Batalkan resep</button>
@endif</div>
@if($visit->prescription->status==='dispensed')<details class="border-t border-slate-100 p-5"><summary class="cursor-pointer text-sm font-semibold">Retur obat ke karantina</summary><form wire:submit="patientReturn" class="mt-4 form-grid"><label><span class="label">Alokasi batch</span><select class="field" wire:model="allocationId"><option value="">Pilih alokasi…</option>
@foreach($visit->prescription->items as $item)
@foreach($item->allocations as $a)<option value="{{ $a->id }}">#{{ $a->id }} · {{ $item->medicine->name }} · {{ $a->batch->batch_number }}</option>
@endforeach
@endforeach</select></label><x-ui.field label="Jumlah retur" model="returnQuantity" type="number"/><x-ui.field label="Alasan retur" model="reason"/><button class="btn-secondary self-end" wire:loading.attr="disabled">Catat retur karantina</button></form></details>
@endif
@endcan</section>
@endif
@endcan
@can('invoices.view')
@if($invoice)<section class="card mb-6"><div class="card-header"><div><h2 class="font-semibold">04 · Billing & pembayaran</h2><p class="mt-1 text-xs text-slate-400">{{ $invoice->number }}</p></div><x-ui.badge :status="$invoice->status"/></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Rincian tagihan</th><th>Jumlah</th><th>Harga satuan</th><th>Total</th></tr></thead><tbody>
@foreach($invoice->items as $item)<tr><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ \App\Support\Money::format($item->unit_price) }}</td><td>{{ \App\Support\Money::format($item->total) }}</td></tr>
@endforeach</tbody></table></div><div class="border-t border-slate-100 p-5"><div class="mb-4 text-right"><p class="text-xs text-slate-400">Diskon {{ \App\Support\Money::format($invoice->discount) }}</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ \App\Support\Money::format(\App\Support\Money::sub((string)$invoice->items->sum('total'),$invoice->discount)) }}</p></div>
@can('invoices.discount')
@if($invoice->status==='draft')<form wire:submit="setDiscount" class="mb-4 flex flex-wrap items-end gap-3"><x-ui.field label="Diskon nominal (Rp)" model="discount" type="number"/><x-ui.field label="Alasan diskon" model="reason"/><button class="btn-secondary">Terapkan diskon</button></form>
@endif
@endcan
@can('payments.create')
@if($invoice->status==='draft')<button wire:click="issue" class="btn" wire:loading.attr="disabled">Terbitkan invoice</button><p class="mt-2 text-xs text-slate-400">Pemeriksaan dan proses resep harus selesai.</p>
@elseif($invoice->status==='issued')<form wire:submit="pay" class="form-grid"><label><span class="label">Metode pembayaran</span><select class="field" wire:model="paymentMethod"><option value="">Pilih metode…</option>
@foreach($methods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>
@endforeach</select></label><x-ui.field label="Uang diterima (Rp)" model="received" type="number" step="0.01"/><x-ui.field label="Referensi transfer / QRIS" model="reference"/><button class="btn self-end" wire:loading.attr="disabled" wire:confirm="Konfirmasi pembayaran sudah diterima?">Terima pembayaran penuh</button></form>
@endif
@endcan
@foreach($invoice->payments as $payment)<div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-4"><div><p class="text-sm font-semibold">{{ $payment->number }} · {{ \App\Support\Money::format($payment->amount) }}</p><p class="mt-1 text-xs text-slate-500">{{ $payment->method->name }} · Kembalian {{ \App\Support\Money::format($payment->change) }} · {{ $payment->void?'DIBATALKAN':'Diterima' }}</p></div>
@can('payments.void')
@unless($payment->void)<button wire:click="voidPayment({{ $payment->id }})" wire:confirm="Batalkan pembayaran dan buka kembali saldo invoice?" class="btn-danger">Void pembayaran</button>
@endunless
@endcan</div>
@endforeach
@can('payments.void')<details class="mt-5"><summary class="cursor-pointer text-sm font-semibold text-slate-600">Koreksi transaksi terotorisasi</summary><div class="mt-3 form-grid"><x-ui.field label="Alasan void / koreksi (wajib)" model="reason"/><x-ui.field label="ID retur pasien untuk koreksi obat (opsional)" model="returnId" type="number"/></div>
@if(in_array($invoice->status,['draft','issued']))<button class="btn-danger mt-3" wire:click="replaceInvoice" wire:confirm="Void invoice ini dan buat invoice pengganti?">Void & buat invoice pengganti</button>
@endif</details>
@endcan<a class="btn-secondary mt-5" href="{{ route('print.receipt',$invoice) }}" target="_blank">Cetak invoice / kuitansi PDF</a></div></section>
@endif
@endcan</div>