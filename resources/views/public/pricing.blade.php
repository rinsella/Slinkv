@extends('layouts.public')
@section('content')
<section class="py-20">
  <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider">Beta · Gratis Untuk Semua</div>
    <h1 class="mt-5 text-4xl sm:text-5xl font-extrabold tracking-tight">Selama Beta, Semua Fitur Gratis</h1>
    <p class="mt-4 text-lg text-muted">Kami sedang dalam tahap pengembangan aktif. Daftar sekarang untuk mendapat akses penuh tanpa biaya apa pun — tanpa kartu kredit, tanpa batasan.</p>

    <div class="mt-10 rounded-2xl bg-white border border-line shadow-card p-8 text-left">
      <div class="text-xs font-bold text-primary uppercase">Yang Kamu Dapat</div>
      <div class="mt-2 text-3xl font-extrabold">Rp 0 <span class="text-base font-medium text-muted">/ selamanya selama beta</span></div>

      <ul class="mt-6 space-y-3 text-sm">
        @foreach ([
          'Link pendek unlimited',
          'Klik unlimited per link',
          'Custom alias (link pendek sesuai keinginan)',
          'QR Code untuk setiap link',
          'Analytics real-time (retensi 365 hari)',
          'Bot protection canggih (multi-layer)',
          'Geo filter (whitelist/blacklist negara)',
          'Device filter (desktop / mobile / tablet)',
          'Fallback URL & link expiration',
          'Export CSV & audit report',
        ] as $f)
          <li class="flex items-start gap-3"><span class="mt-0.5 w-5 h-5 rounded-full bg-green-100 text-green-600 inline-flex items-center justify-center text-xs font-bold">✓</span><span>{{ $f }}</span></li>
        @endforeach
      </ul>

      <a href="@auth{{ route('dashboard.index') }}@else{{ route('register') }}@endauth" class="mt-8 inline-block w-full text-center px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary-700">@auth Buka Dashboard @else Daftar Gratis @endauth</a>
    </div>

    <p class="mt-6 text-xs text-muted">Paket berbayar akan diumumkan setelah masa beta selesai. Pengguna existing tetap menikmati akses gratis.</p>
  </div>
</section>
@endsection
