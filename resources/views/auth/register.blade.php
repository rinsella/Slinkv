@extends('layouts.base')
@section('body')
<div class="min-h-screen grid lg:grid-cols-2">
  <div class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-primary to-secondary text-white p-12">
    <a href="{{ route('home') }}" class="text-3xl"><span class="text-white">slink</span><span class="text-white/80">v</span></a>
    <div>
      <h2 class="text-4xl font-extrabold leading-tight">Daftar gratis, mulai pendekkan link sekarang.</h2>
      <ul class="mt-6 space-y-2 text-white/90 text-sm">
        <li>✓ Semua fitur premium gratis selama beta</li>
        <li>✓ Unlimited link selama beta</li>
        <li>✓ Bot protection advanced</li>
        <li>✓ Analytics lengkap</li>
        <li>✓ Custom alias, QR code, fallback URL</li>
        <li>✓ Tidak perlu kartu kredit</li>
      </ul>
    </div>
    <div class="text-sm text-white/70">© {{ date('Y') }} SlinkV</div>
  </div>
  <div class="flex items-center justify-center p-6 sm:p-12">
    <div class="w-full max-w-md">
      <a href="{{ route('home') }}" class="lg:hidden text-2xl"><span class="brand-slink">slink</span><span class="brand-v">v</span></a>
      <h1 class="mt-6 text-3xl font-bold">Buat Akun</h1>
      <p class="text-muted text-sm mt-1">100% gratis selama tahap beta.</p>

      @if ($errors->any())<div class="mt-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-200">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

      <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        <div><label class="block text-sm font-medium mb-1">Nama Lengkap</label><input name="name" required value="{{ old('name') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
        <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" required value="{{ old('email') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
        <div><label class="block text-sm font-medium mb-1">Password</label><input type="password" name="password" required minlength="8" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"><div class="text-xs text-muted mt-1">Minimal 8 karakter.</div></div>
        <div><label class="block text-sm font-medium mb-1">Konfirmasi Password</label><input type="password" name="password_confirmation" required class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
        <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="terms" value="1" required class="mt-1 rounded border-line text-primary focus:ring-primary"><span>Saya menyetujui <a href="{{ route('terms') }}" class="text-primary underline">Syarat</a> & <a href="{{ route('privacy') }}" class="text-primary underline">Privasi</a>.</span></label>
        <button class="w-full px-4 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary-700">Daftar Gratis</button>
      </form>

      <div class="mt-6 text-center text-sm text-muted">Sudah punya akun? <a href="{{ route('login') }}" class="text-primary font-semibold">Masuk</a></div>
    </div>
  </div>
</div>
@endsection
