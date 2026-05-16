@extends('layouts.dashboard')
@section('content')
<div class="max-w-2xl">
  <h1 class="text-2xl font-bold">Billing</h1>
  <p class="text-muted mt-1 text-sm">Status langganan akun Anda.</p>

  <div class="mt-6 rounded-2xl bg-gradient-to-br from-primary to-secondary text-white p-8 shadow-card">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-[10px] font-bold uppercase tracking-wider">Beta</div>
    <div class="mt-3 text-3xl font-extrabold">100% Gratis</div>
    <p class="mt-2 text-white/85 text-sm">Selama tahap beta, seluruh fitur SlinkV tersedia gratis untuk akun Anda - tanpa batasan link, klik, atau fitur premium.</p>
  </div>

  <div class="mt-6 rounded-2xl bg-white border border-line p-6">
    <div class="text-sm font-semibold">Tidak ada riwayat pembayaran</div>
    <p class="text-sm text-muted mt-1">Sistem pembayaran belum aktif. Akun Anda otomatis mendapatkan akses penuh selama masa beta.</p>
  </div>
</div>
@endsection
