@extends('layouts.admin')
@section('title', 'Edit User: '.$user->name)
@section('content')
<a href="{{ route('admin.users.show', $user) }}" class="text-sm text-primary">← Kembali</a>
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-3 bg-white rounded-2xl border border-line p-6 max-w-2xl space-y-4">
  @csrf @method('PUT')
  <div><label class="block text-sm font-medium mb-1">Nama</label><input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border-line"></div>
  <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-xl border-line"></div>
  <div class="grid grid-cols-2 gap-3">
    <div><label class="block text-sm font-medium mb-1">Role</label><select name="role" class="w-full rounded-xl border-line">@foreach (['user','admin'] as $r)<option value="{{ $r }}" @selected(old('role', $user->role)===$r)>{{ ucfirst($r) }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium mb-1">Status</label><select name="status" class="w-full rounded-xl border-line">@foreach (['active','suspended','deleted'] as $s)<option value="{{ $s }}" @selected(old('status', $user->status)===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
  </div>
  <div><label class="block text-sm font-medium mb-1">Plan</label><select name="plan_id" class="w-full rounded-xl border-line"><option value="">- Tidak ada -</option>@foreach ($plans as $p)<option value="{{ $p->id }}" @selected(old('plan_id', $user->plan_id)==$p->id)>{{ $p->name }}</option>@endforeach</select></div>
  <div><label class="block text-sm font-medium mb-1">Password Baru <span class="text-muted text-xs">(opsional)</span></label><input type="password" name="password" class="w-full rounded-xl border-line"></div>
  <div class="flex gap-2"><button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan</button><a href="{{ route('admin.users.show', $user) }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-ink font-semibold text-sm">Batal</a></div>
</form>
@endsection
