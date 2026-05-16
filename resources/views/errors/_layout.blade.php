@extends('layouts.base')
@section('body')
<div class="min-h-screen flex items-center justify-center px-4">
  <div class="max-w-md w-full text-center">
    <a href="{{ route('home') }}" class="text-3xl"><span class="brand-slink">slink</span><span class="brand-v">v</span></a>
    <div class="mt-8 text-7xl font-extrabold text-primary">{{ $code }}</div>
    <h1 class="mt-4 text-2xl font-bold">{{ $title }}</h1>
    <p class="mt-3 text-muted">{{ $message }}</p>
    <div class="mt-8 flex gap-3 justify-center flex-wrap">
      <a href="{{ url()->previous() }}" class="px-4 py-2 rounded-xl border border-line bg-white text-sm font-semibold hover:bg-surface">← Kembali</a>
      <a href="{{ route('home') }}" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-700">Beranda</a>
      @auth<a href="{{ route('dashboard.index') }}" class="px-4 py-2 rounded-xl bg-ink text-white text-sm font-semibold">Dashboard</a>@endauth
    </div>
  </div>
</div>
@endsection
