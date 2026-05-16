@extends('layouts.base')
@section('body')
<div class="min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-md">
    <a href="{{ route('home') }}" class="text-2xl"><span class="brand-slink">slink</span><span class="brand-v">v</span></a>
    <h1 class="mt-6 text-3xl font-bold">Lupa Password</h1>
    <p class="text-muted text-sm mt-1">Masukkan email akun Anda, kami akan kirim link reset.</p>
    @if (session('status'))<div class="mt-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-200">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="mt-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-200">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
      @csrf
      <input type="email" name="email" required placeholder="Email" value="{{ old('email') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
      <button class="w-full px-4 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary-700">Kirim Link Reset</button>
    </form>
    <a href="{{ route('login') }}" class="mt-4 inline-block text-sm text-primary">← Kembali ke login</a>
  </div>
</div>
@endsection
