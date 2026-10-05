<div wire:poll.20s><div class="mb-6 flex flex-wrap justify-between gap-3"><div><p class="eyebrow mb-2 text-teal-600">{{ $area==='pharmacy'?'Farmasi klinik':'Pelayanan rawat jalan' }}</p><h1 class="page-title">{{ $area==='pharmacy'?'Pelayanan apotek':'Kunjungan & antrean' }}</h1><p class="subtitle">{{ $area==='pharmacy'?'Verifikasi resep, siapkan obat, dan serahkan berdasarkan FEFO.':'Kelola pendaftaran dan perjalanan pasien dalam satu alur.' }}</p></div>
@can('visits.register')<button wire:click="create" class="btn self-start"><x-ui.icon name="plus"/>Daftarkan kunjungan</button>
@endcan</div><x-ui.feedback/>
<section class="card"><div class="card-header"><input class="field max-w-sm" placeholder="Cari pasien atau nomor RM…" wire:model.live.debounce.350ms="search" aria-label="Cari kunjungan"><select class="field max-w-xs" wire:model.live="status" aria-label="Filter status"><option value="">Semua status</option>
@foreach(\App\Enums\VisitStatus::cases() as $s)<option value="{{ $s->value }}">{{ str_replace('_',' ',$s->value) }}</option>
@endforeach</select></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Antrean</th><th>Pasien / kunjungan</th><th>Poli / dokter</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($rows as $visit)<tr><td><span class="rounded-lg bg-teal-50 px-3 py-2 font-mono text-lg font-semibold text-teal-700">{{ str_pad($visit->queue->number,3,'0',STR_PAD_LEFT) }}</span></td><td><p class="font-semibold text-slate-800">{{ $visit->patient->name }}</p><p class="mt-1 text-[11px] text-slate-400">{{ $visit->patient->medical_number }} · {{ $visit->visit_date->format('d M Y') }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $visit->number }}</p></td><td>{{ $visit->polyclinic->name }}<p class="mt-1 text-xs text-slate-400">{{ $visit->doctor->name }}</p></td><td><x-ui.badge :status="$visit->status"/><p class="mt-1 text-[10px] text-slate-400">Antrean: {{ $visit->queue->status }}</p></td><td><div class="flex items-center gap-4">
@can('queues.manage')
@if(in_array($visit->status,['waiting_triage','waiting_doctor']))<button wire:click="call({{ $visit->id }})" class="link text-xs">Panggil</button>
@endif
@endcan<a class="btn-secondary text-xs" href="{{ route('visits.show',$visit) }}" wire:navigate>Buka layanan →</a></div></td></tr>
@empty<tr><td colspan="5"><x-ui.empty/></td></tr>
@endforelse</tbody></table></div><div class="p-4">{{ $rows->links() }}</div></section>
@if($showForm)<x-ui.modal title="Daftarkan kunjungan"><x-ui.feedback/><form wire:submit="register" class="space-y-4"><label><span class="label">Cari pasien terdaftar</span><input class="field" wire:model.live.debounce.350ms="patientSearch" placeholder="Nama atau nomor RM"></label><label class="block"><span class="label">Pasien *</span><select class="field" wire:model="form.patient_id"><option value="">Pilih pasien…</option>
@foreach($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->medical_number }} · {{ $patient->name }}</option>
@endforeach</select></label><div class="form-grid">
@foreach(['polyclinic_id'=>['Poli',$polys],'medical_staff_id'=>['Dokter',$doctors],'service_tariff_id'=>['Konsultasi',$tariffs]] as $key=>[$label,$options])<label><span class="label">{{ $label }} *</span><select class="field" wire:model="form.{{ $key }}"><option value="">Pilih…</option>
@foreach($options as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>
@endforeach</select></label>
@endforeach<x-ui.field label="Penjamin / pembayar" model="form.payer"/></div><div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-500"><p class="mb-2 font-semibold">Jadwal hari ini</p>
@foreach($schedules as $s)<p>{{ $s->staff->name }} · {{ $s->polyclinic->name }} · {{ substr($s->starts_at,0,5) }}–{{ substr($s->ends_at,0,5) }} · kuota {{ $s->capacity }}</p>
@endforeach</div><button class="btn w-full" wire:loading.attr="disabled">Buat kunjungan & nomor antrean</button></form></x-ui.modal>
@endif</div>