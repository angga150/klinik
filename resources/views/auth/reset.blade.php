
@extends('layouts.guest') 
@section('content')<h2 class="page-title mb-6">Kata sandi baru</h2>
@if($errors->any())<p class="mb-4 text-red-600">{{ $errors->first() }}</p>
@endif<form method="post" action="{{ route('password.update') }}" class="space-y-4">
@csrf<input type="hidden" name="token" value="{{ $token }}"><label><span class="label">Email</span><input class="field" name="email" type="email" value="{{ request('email') }}" required></label><label><span class="label">Kata sandi baru (minimal 12 karakter)</span><input class="field" name="password" type="password" minlength="12" required></label><label><span class="label">Konfirmasi kata sandi</span><input class="field" name="password_confirmation" type="password" required></label><button class="btn w-full">Simpan kata sandi</button></form>
@endsection