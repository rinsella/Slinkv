@extends('layouts.admin')
@section('content')
<div class="max-w-2xl">
  <a href="{{ route('admin.users.index') }}" class="text-sm text-primary">← Daftar User</a>
  <h1 class="mt-1 text-2xl font-bold">Tambah User Baru</h1>
  <p class="text-sm text-muted">User dibuat dengan email yang sudah terverifikasi.</p>

  <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 bg-white rounded-2xl shadow-card border border-line p-6 space-y-4">
    @csrf
    @if ($errors->any())
      <div class="px-3 py-2 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">
        <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <div>
      <label class="block text-sm font-medium mb-1">Nama</label>
      <input name="name" required maxlength="120" value="{{ old('name') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Email</label>
      <input type="email" name="email" required value="{{ old('email') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Password (min 8 karakter)</label>
      <input type="password" name="password" required minlength="8" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Role</label>
        <select name="role" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
          @foreach (['user' => 'User', 'admin' => 'Admin'] as $k => $v)
            <option value="{{ $k }}" @selected(old('role', 'user') === $k)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Status</label>
        <select name="status" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
          @foreach (['active' => 'Active', 'suspended' => 'Suspended'] as $k => $v)
            <option value="{{ $k }}" @selected(old('status', 'active') === $k)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Paket</label>
        <select name="plan_id" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
          <option value="">— Default (Free) —</option>
          @foreach ($plans as $p)
            <option value="{{ $p->id }}" @selected(old('plan_id') == $p->id)>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="flex gap-2 pt-2">
      <button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-primary-700">Buat User</button>
      <a href="{{ route('admin.users.index') }}" class="px-5 py-2.5 rounded-xl border border-line">Batal</a>
    </div>
  </form>
</div>
@endsection
