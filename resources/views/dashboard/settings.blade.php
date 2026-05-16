@extends('layouts.dashboard')
@section('content')
<h1 class="text-2xl font-bold mb-6">Pengaturan</h1>

<div class="grid lg:grid-cols-2 gap-5">
  <div class="bg-white rounded-2xl shadow-card border border-line p-6">
    <h3 class="font-semibold mb-4">Profil</h3>
    <form method="POST" action="{{ route('dashboard.settings.profile') }}" class="space-y-4">
      @csrf @method('PUT')
      <div><label class="block text-sm font-medium mb-1">Nama</label><input name="name" required value="{{ old('name', auth()->user()->name) }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
      <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" required value="{{ old('email', auth()->user()->email) }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
      <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-700">Simpan Profil</button>
    </form>
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line p-6">
    <h3 class="font-semibold mb-4">Ubah Password</h3>
    <form method="POST" action="{{ route('dashboard.settings.password') }}" class="space-y-4">
      @csrf @method('PUT')
      <div><label class="block text-sm font-medium mb-1">Password Saat Ini</label><input type="password" name="current_password" required class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
      <div><label class="block text-sm font-medium mb-1">Password Baru</label><input type="password" name="password" required minlength="8" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
      <div><label class="block text-sm font-medium mb-1">Konfirmasi</label><input type="password" name="password_confirmation" required class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
      <button class="px-4 py-2 rounded-xl bg-ink text-white text-sm font-semibold">Update Password</button>
    </form>
  </div>
</div>
@endsection
