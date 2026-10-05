<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title ?? 'Pusat kendali' }} · SIM Klinik Enterprise</title>
@vite(['resources/css/app.css','resources/js/app.js'])
@livewireStyles</head>
<body x-data="{sidebar:false,compact:false}">
<div x-show="sidebar" x-cloak @click="sidebar=false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
<aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-[#101f32] transition-transform lg:translate-x-0" :class="[sidebar ? 'translate-x-0' : '-translate-x-full', compact ? 'lg:hidden' : '']">
 <a href="{{ route('dashboard') }}" wire:navigate class="flex h-21 items-center gap-3 border-b border-white/5 px-6"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-400 text-[#101f32]"><x-ui.icon name="plus" class="h-7 w-7"/></span><span class="text-lg font-bold tracking-tight text-white">klinik<span class="font-light text-teal-300">enterprise</span><span class="mt-0.5 block text-[9px] font-medium tracking-[0.23em] text-slate-500">CONNECTED CARE WORKSPACE</span></span></a>
 <div class="mx-4 mt-5 flex items-center gap-3 rounded-lg border border-white/10 bg-white/[.03] px-3 py-3"><span class="h-2 w-2 rounded-full bg-teal-400"></span><div><p class="text-xs font-semibold text-slate-200">{{ auth()->user()->clinic->name }}</p><p class="mt-1 text-[10px] text-slate-500">Klinik rawat jalan · Demo lokal</p></div></div>
 <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6" aria-label="Navigasi utama"><p class="eyebrow mb-3 px-3">Workspace</p>
 
@php($nav=[['dashboard','dashboard.view','grid','Pusat kendali'],['patients','patients.view','users','Data pasien'],['visits','visits.view','queue','Kunjungan & antrean'],['pharmacy','prescriptions.view','pill','Pelayanan apotek'],['inventory','inventory.view','box','Persediaan obat'],['billing','invoices.view','wallet','Billing & kasir'],['reports','reports.view','chart','Laporan & analitik']])
 
@foreach($nav as [$route,$permission,$icon,$label])
@can($permission)<a href="{{ route($route) }}" wire:navigate @class(['nav-link','active'=>request()->routeIs($route) || ($route==='visits' && request()->routeIs('visits.show'))])><x-ui.icon :name="$icon"/>{{ $label }}</a>
@endcan
@endforeach
 <p class="eyebrow mb-3 mt-7 px-3">Administrasi</p>
 
@can('settings.manage')<a href="{{ route('masters') }}" wire:navigate @class(['nav-link','active'=>request()->routeIs('masters')])><x-ui.icon name="settings"/>Master data</a>
@endcan
 
@can('users.manage')<a href="{{ route('users') }}" wire:navigate @class(['nav-link','active'=>request()->routeIs('users')])><x-ui.icon name="shield"/>Pengguna & akses</a>
@endcan
 
@can('audit.view')<a href="{{ route('audit') }}" wire:navigate @class(['nav-link','active'=>request()->routeIs('audit')])><x-ui.icon name="clock"/>Audit aktivitas</a>
@endcan
 <a href="{{ route('profile') }}" wire:navigate @class(['nav-link','active'=>request()->routeIs('profile')])><x-ui.icon name="settings"/>Profil & klinik</a>
 </nav>
 <div class="m-4 rounded-xl border border-teal-400/15 bg-teal-400/5 p-4"><x-ui.icon name="shield" class="mb-2 text-teal-400"/><p class="text-xs font-semibold text-slate-200">Pelayanan yang terhubung</p><p class="mt-1.5 text-[11px] leading-relaxed text-slate-500">Satu alur, dari pendaftaran hingga pelayanan selesai.</p></div>
 <div class="border-t border-white/5 px-6 py-4 text-[10px] text-slate-600">SIM KLINIK ENTERPRISE <span class="float-right">v1.0 · TA</span></div>
</aside>
<div :class="compact ? 'lg:pl-0' : 'lg:pl-64'"><header class="sticky top-0 z-20 flex h-21 items-center justify-between gap-4 border-b border-slate-200/70 bg-white/95 px-5 backdrop-blur lg:px-8"><div class="flex items-center gap-3"><button @click="compact=!compact" class="hidden text-slate-500 lg:block" aria-label="Ciutkan navigasi"><x-ui.icon name="menu"/></button><button @click="sidebar=!sidebar" class="text-slate-500 lg:hidden" aria-label="Buka navigasi"><x-ui.icon name="menu"/></button><div class="hidden text-xs text-slate-400 sm:block">Workspace <span class="mx-2">/</span><span class="font-medium text-slate-700">{{ $title ?? 'Pusat kendali' }}</span></div></div><div class="flex items-center gap-5"><span class="hidden text-xs text-slate-400 xl:block">{{ now()->translatedFormat('l, d F Y') }}</span>
@can('patients.view')<a href="{{ route('patients') }}" wire:navigate aria-label="Cari pasien" class="text-slate-400"><x-ui.icon name="search"/></a>
@endcan<div class="h-7 border-l border-slate-200"></div><a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-teal-50 text-sm font-bold text-teal-700">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span class="hidden sm:block"><span class="block text-xs font-semibold text-slate-800">{{ auth()->user()->name }}</span><span class="mt-0.5 block text-[10px] text-slate-400">{{ auth()->user()->getRoleNames()->first() }}</span></span></a><form method="post" action="{{ route('logout') }}">
@csrf<button aria-label="Keluar" title="Keluar" class="text-slate-400 hover:text-red-600"><x-ui.icon name="logout"/></button></form></div></header>
<main class="mx-auto max-w-[1600px] p-5 lg:p-8">{{ $slot }}</main><footer class="flex justify-between px-8 pb-6 text-[10px] text-slate-400"><span>© {{ date('Y') }} SIM Klinik Enterprise</span><span>Prototype akademis · Data demonstrasi</span></footer></div>
@livewireScripts</body></html>
