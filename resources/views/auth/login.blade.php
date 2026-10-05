
@extends('layouts.guest') 
@section('content')<p class="eyebrow mb-3 text-teal-600">Selamat datang kembali</p><h2 class="text-3xl font-bold tracking-tight">Masuk ke workspace</h2><p class="subtitle mb-8">Gunakan akun petugas untuk memulai pelayanan.</p>
@if(session('status'))<p class="notice mb-5">{{ session('status') }}</p>
@endif 
@if($errors->any())<p class="mb-4 text-sm text-red-600" role="alert">{{ $errors->first() }}</p>
@endif<form method="post" action="/login" class="space-y-5">
@csrf<label class="block"><span class="label">Alamat email</span><input class="field" name="email" type="email" value="{{ old('email') }}" placeholder="nama@klinik.test" autocomplete="username" required autofocus></label><label class="block"><span class="label">Kata sandi</span><input class="field" name="password" type="password" autocomplete="current-password" required></label><div class="text-right"><a class="link text-xs" href="{{ route('password.request') }}">Lupa kata sandi?</a></div><button class="btn w-full py-3">Masuk workspace <x-ui.icon name="arrow" class="ml-4 h-4 w-4"/></button></form><p class="mt-8 border-t border-slate-100 pt-5 text-center text-xs leading-6 text-slate-400">Akses berdasarkan peran. Hubungi administrator klinik untuk pembuatan akun.</p>
@endsection