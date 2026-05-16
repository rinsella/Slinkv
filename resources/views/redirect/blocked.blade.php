@extends('layouts.base')
@php
  $msg = match($reason ?? 'security') {
    'bot' => 'Aktivitas yang terdeteksi seperti bot diblokir untuk melindungi link ini.',
    'geo' => 'Link ini tidak tersedia di wilayah Anda.',
    'device' => 'Perangkat Anda tidak diizinkan mengakses link ini.',
    default => 'Akses Anda diblokir oleh sistem keamanan SlinkV.',
  };
@endphp
@section('body')
<div class="min-h-screen flex items-center justify-center px-4">
  <div class="max-w-md w-full text-center">
    <a href="{{ route('home') }}" class="text-3xl"><span class="brand-slink">slink</span><span class="brand-v">v</span></a>
    <div class="mt-8 text-6xl">🛡️</div>
    <h1 class="mt-4 text-2xl font-bold">Akses Diblokir</h1>
    <p class="mt-3 text-muted">{{ $msg }}</p>
    <a href="{{ route('home') }}" class="mt-6 inline-block px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Beranda</a>
  </div>
</div>
@endsection
