@extends('layouts.base')
@section('body')
<div class="min-h-screen grid lg:grid-cols-2">
  <div class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-primary to-secondary text-white p-12">
    <a href="{{ route('home') }}" class="text-3xl"><span class="text-white">slink</span><span class="text-white/80">v</span></a>
    <div>
      <h2 class="text-4xl font-extrabold leading-tight">Selamat datang kembali.</h2>
      <p class="mt-3 text-white/80 max-w-md">Kelola shortlink, pantau analytics real-time, dan lindungi traffic Anda dari bot.</p>
    </div>
    <div class="text-sm text-white/70">© {{ date('Y') }} SlinkV</div>
  </div>
  <div class="flex items-center justify-center p-6 sm:p-12">
    <div class="w-full max-w-md">
      <a href="{{ route('home') }}" class="lg:hidden text-2xl"><span class="brand-slink">slink</span><span class="brand-v">v</span></a>
      <h1 class="mt-6 text-3xl font-bold">Masuk</h1>
      <p class="text-muted text-sm mt-1">Akses dashboard SlinkV Anda.</p>

      @if (session('status'))<div class="mt-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-200">{{ session('status') }}</div>@endif
      @if ($errors->any())<div class="mt-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-200">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

      <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf
        <div>
          <label class="block text-sm font-medium mb-1">Email</label>
          <input type="email" name="email" required autofocus value="{{ old('email') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Password</label>
          <input type="password" name="password" required class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
        </div>
        <div class="flex items-center justify-between text-sm">
          <label class="inline-flex items-center gap-2"><input type="checkbox" name="remember" class="rounded border-line text-primary focus:ring-primary"> Ingat saya</label>
          <a href="{{ route('password.request') }}" class="text-primary hover:underline">Lupa password?</a>
        </div>
        <button class="w-full px-4 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary-700">Masuk</button>
      </form>

      <div class="mt-6 text-center text-sm text-muted">Belum punya akun? <a href="{{ route('register') }}" class="text-primary font-semibold">Daftar gratis</a></div>
    </div>
  </div>
</div>
@endsection
