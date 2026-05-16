@extends('layouts.base')
@section('body')
<div class="min-h-screen flex items-center justify-center px-4">
  <div class="max-w-md w-full text-center">
    <a href="{{ route('home') }}" class="text-3xl"><span class="brand-slink">slink</span><span class="brand-v">v</span></a>
    <div class="mt-8 text-6xl">⏰</div>
    <h1 class="mt-4 text-2xl font-bold">Link Sudah Kedaluwarsa</h1>
    <p class="mt-3 text-muted">Maaf, link yang Anda buka sudah tidak berlaku.</p>
    <a href="{{ route('home') }}" class="mt-6 inline-block px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Kembali ke Beranda</a>
  </div>
</div>
@endsection
