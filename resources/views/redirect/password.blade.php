@extends('layouts.base')
@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-12 bg-surface">
  <div class="max-w-md w-full">
    <div class="text-center mb-6">
      <a href="{{ route('home') }}" class="text-3xl"><span class="brand-slink">slink</span><span class="brand-v">v</span></a>
    </div>
    <div class="bg-white border border-line rounded-2xl shadow-card p-6 sm:p-8">
      <div class="text-center">
        <div class="text-5xl">🔒</div>
        <h1 class="mt-3 text-xl sm:text-2xl font-bold">Link Dilindungi Password</h1>
        <p class="mt-2 text-sm text-muted">Masukkan password untuk melanjutkan ke tujuan link.</p>
      </div>

      @if (session('error'))
        <div class="mt-4 px-3 py-2 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="mt-4 px-3 py-2 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ url('/'.$slug.'/unlock') }}" class="mt-5 space-y-3">
        @csrf
        <input type="password" name="password" required autofocus minlength="1" maxlength="64"
          class="w-full rounded-xl border-line focus:ring-primary focus:border-primary text-sm"
          placeholder="Password">
        <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-700 text-white text-sm font-semibold">
          Buka Link
        </button>
      </form>

      <div class="mt-5 text-center text-xs text-muted">
        Link: <span class="font-mono">{{ url('/'.$slug) }}</span>
      </div>
    </div>
    <div class="mt-4 text-center text-xs text-muted">
      <a href="{{ route('home') }}" class="hover:underline">← Kembali ke beranda</a>
    </div>
  </div>
</div>
@endsection
