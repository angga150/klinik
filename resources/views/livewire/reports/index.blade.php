<div><div class="mb-6"><h1 class="page-title">Laporan & analitik</h1><p class="subtitle">Data pelayanan, persediaan, dan penerimaan sesuai periode.</p></div><x-ui.feedback/><section class="card mb-5 p-5"><div class="grid gap-4 md:grid-cols-3"><label><span class="label">Jenis laporan</span><select class="field" wire:model.live="report">
@foreach($types as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>
@endforeach</select></label><x-ui.field label="Dari tanggal" model="filters.from" type="date"/><x-ui.field label="Sampai tanggal" model="filters.to" type="date"/>
@if(in_array($report,['visits','diagnoses','procedures','prescriptions']))<label><span class="label">Poli</span><select class="field" wire:model="filters.polyclinic_id"><option value="">Semua poli</option>
@foreach($polys as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>
@endforeach</select></label><label><span class="label">Dokter</span><select class="field" wire:model="filters.medical_staff_id"><option value="">Semua dokter</option>
@foreach($doctors as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>
@endforeach</select></label><label><span class="label">Status kunjungan</span><select class="field" wire:model="filters.status"><option value="">Semua status</option>
@foreach(\App\Enums\VisitStatus::cases() as $s)<option>{{ $s->value }}</option>
@endforeach</select></label>
@endif</div><div class="mt-4 flex flex-wrap gap-3"><button wire:click="$refresh" class="btn">Terapkan filter</button><button wire:click="export('pdf')" class="btn-secondary" wire:loading.attr="disabled">Ekspor PDF</button><button wire:click="export('xlsx')" class="btn-secondary" wire:loading.attr="disabled">Ekspor Excel</button></div>
@if($report==='stock')<p class="mt-3 text-xs text-slate-400">Posisi stok menunjukkan saldo saat ini. Gunakan laporan mutasi untuk penelusuran periode.</p>
@endif</section><section class="card"><div class="card-header"><h2 class="font-semibold">{{ $types[$report] }}</h2><span class="text-xs text-slate-400">{{ $rows->total() }} baris</span></div><div class="table-wrap"><table class="data-table">
@if($rows->count())<thead><tr>
@foreach(array_keys((array)$rows->first()) as $heading)<th>{{ $heading }}</th>
@endforeach</tr></thead><tbody>
@foreach($rows as $row)<tr>
@foreach((array)$row as $value)<td>{{ $value }}</td>
@endforeach</tr>
@endforeach</tbody>
@endif</table>
@unless($rows->count())<x-ui.empty>Tidak ada data pada periode ini.</x-ui.empty>
@endunless</div><div class="p-4">{{ $rows->links() }}</div></section><section class="card mt-6" wire:poll.5s><div class="card-header"><h2 class="text-sm font-semibold">Unduhan laporan Anda</h2><span class="text-xs text-slate-400">10 ekspor terbaru</span></div><div class="divide-y divide-slate-100">
@forelse($exports as $export)<div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"><div><p class="text-sm font-semibold">{{ $types[$export->report] }} · {{ strtoupper($export->format) }}</p><p class="mt-1 text-xs text-slate-400">{{ $export->created_at->format('d M Y H:i') }} {{ $export->error }}</p></div>
@if($export->status==='completed')<a href="{{ route('exports.download',$export) }}" class="link text-sm">Unduh file ↓</a>
@else<x-ui.badge :status="$export->status"/>
@endif</div>
@empty<x-ui.empty>Ekspor laporan akan muncul di sini.</x-ui.empty>
@endforelse</div></section></div>