<div><div class="mb-6 flex items-center justify-between"><div><h1 class="page-title">Master data</h1><p class="subtitle">Kelola referensi pelayanan dan operasional klinik.</p></div><button wire:click="create" class="btn"><x-ui.icon name="plus"/>Tambah {{ strtolower($catalog['title']) }}</button></div><x-ui.feedback/>
<div class="mb-5 flex flex-wrap gap-2">
@foreach(config('masters') as $key=>$master)<a wire:navigate href="{{ route('masters',$key) }}" @class(['rounded-lg border px-3 py-2 text-xs font-medium','border-teal-600 bg-teal-600 text-white'=>$resource===$key,'border-slate-200 bg-white text-slate-500'=>$resource!==$key])>{{ $master['title'] }}</a>
@endforeach</div>
<section class="card"><div class="card-header"><h2 class="font-semibold">{{ $catalog['title'] }}</h2><input wire:model.live.debounce.350ms="search" class="field max-w-xs" placeholder="Cari nama…" aria-label="Cari master data"></div><div class="table-wrap"><table class="data-table"><thead><tr>
@foreach(array_slice($catalog['fields'],0,5) as $f)<th>{{ $f['label'] }}</th>
@endforeach<th>Status</th><th></th></tr></thead><tbody>
@forelse($rows as $row)<tr>
@foreach(array_slice($catalog['fields'],0,5) as $key=>$f)<td>{{ isset($options[$key]) ? ($options[$key]->firstWhere('id',$row->$key)?->name ?? '#'.$row->$key) : $row->$key }}</td>
@endforeach<td><span class="text-xs {{ $row->active?'text-teal-600':'text-slate-400' }}">{{ $row->active?'Aktif':'Nonaktif' }}</span></td><td><button wire:click="edit({{ $row->id }})" class="link text-xs">Edit</button></td></tr>
@empty<tr><td colspan="7"><x-ui.empty/></td></tr>
@endforelse</tbody></table></div><div class="p-4">{{ $rows->links() }}</div></section>
@if($showForm)<x-ui.modal :title="($editing?'Edit ':'Tambah ').$catalog['title']"><x-ui.feedback/><form wire:submit="save"><div class="form-grid">
@foreach($catalog['fields'] as $key=>$field)
@if(isset($options[$key]))<label><span class="label">{{ $field['label'] }}</span><select class="field" wire:model="form.{{ $key }}"><option value="">Pilih…</option>
@foreach($options[$key] as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>
@endforeach</select></label>
@elseif(str_contains($field['type'],','))<label><span class="label">{{ $field['label'] }}</span><select class="field" wire:model="form.{{ $key }}"><option value="">Pilih…</option>
@foreach(explode(',',$field['type']) as $option)<option>{{ $option }}</option>
@endforeach</select></label>
@else<x-ui.field :label="$field['label']" :model="'form.'.$key" :type="$field['type']" step="any"/>
@endif 
@endforeach<label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.active">Data aktif</label></div><div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="$set('showForm',false)" class="btn-secondary">Batal</button><button class="btn" wire:loading.attr="disabled">Simpan data</button></div></form></x-ui.modal>
@endif</div>