
@extends('layouts.guest') 
@section('content')<h2 class="page-title">Reset kata sandi</h2><p class="subtitle mb-6">Masukkan email akun Anda.</p>
@if(session('status'))<p class="notice mb-4">{{ session('status') }}</p>
@endif 
@if($errors->any())<p class="text-red-600">{{ $errors->first() }}</p>
@endif<form method="post" action="{{ route('password.email') }}" class="space-y-4">
@csrf<label><span class="label">Email</span><input class="field" name="email" type="email" required></label><button class="btn w-full">Kirim tautan reset</button></form><a class="link mt-6 inline-block text-sm" href="/login">← Kembali ke login</a>
@endsection