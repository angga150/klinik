
@if(session('success'))<div class="notice mb-5" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())<div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"><p class="font-semibold">Periksa kembali data berikut:</p><ul class="mt-2 list-inside list-disc">
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach</ul></div>
@endif
<div wire:loading.delay class="fixed bottom-5 right-5 z-[60] rounded-xl bg-slate-900 px-5 py-3 text-sm text-white shadow-xl" role="status">Memproses…</div>
