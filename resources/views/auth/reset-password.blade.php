@extends('layouts.base')
@section('body')
<div class="min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-md">
    <a href="{{ route('home') }}" class="text-2xl"><span class="brand-slink">slink</span><span class="brand-v">v</span></a>
    <h1 class="mt-6 text-3xl font-bold">Reset Password</h1>
    @if ($errors->any())<div class="mt-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-200">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <input type="email" name="email" required placeholder="Email" value="{{ old('email', $request->email ?? '') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
      <input type="password" name="password" required minlength="8" placeholder="Password baru" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
      <input type="password" name="password_confirmation" required placeholder="Konfirmasi password" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
      <button class="w-full px-4 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary-700">Simpan Password Baru</button>
    </form>
  </div>
</div>
@endsection
