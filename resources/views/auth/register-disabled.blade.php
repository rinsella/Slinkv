@extends('layouts.base')
@section('body')
<div class="min-h-screen flex items-center justify-center px-4">
  <div class="max-w-md w-full text-center">
    <a href="{{ route('home') }}" class="text-3xl"><span class="brand-slink">slink</span><span class="brand-v">v</span></a>
    <div class="mt-8 text-6xl">🔐</div>
    <h1 class="mt-4 text-2xl font-bold">Pendaftaran Ditutup</h1>
    <p class="mt-3 text-muted">Pendaftaran akun baru sedang dinonaktifkan oleh administrator. Silakan coba kembali nanti.</p>
    <div class="mt-6 flex justify-center gap-3">
      <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Login</a>
      <a href="{{ route('home') }}" class="px-4 py-2 rounded-xl border border-line text-sm">Beranda</a>
    </div>
  </div>
</div>
@endsection
