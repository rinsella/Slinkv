@extends('layouts.admin')
@section('title', $ip->exists ? 'Edit IP' : 'Blokir IP')
@section('content')
<a href="{{ route('admin.blocked-ips.index') }}" class="text-sm text-primary">← Kembali</a>
<form method="POST" action="{{ $ip->exists ? route('admin.blocked-ips.update', $ip) : route('admin.blocked-ips.store') }}" class="mt-3 bg-white rounded-2xl border border-line p-6 max-w-xl space-y-4">
  @csrf @if ($ip->exists) @method('PUT') @endif
  @if (!$ip->exists)
    <div><label class="block text-sm font-medium mb-1">IP Address</label><input name="ip" required placeholder="192.168.1.1" class="w-full rounded-xl border-line font-mono"><p class="text-xs text-muted mt-1">Akan di-hash SHA256 sebelum disimpan.</p></div>
  @else
    <div><label class="block text-sm font-medium mb-1">IP Hash</label><input value="{{ $ip->ip_hash }}" readonly class="w-full rounded-xl border-line font-mono bg-slate-50 text-xs"></div>
  @endif
  <div><label class="block text-sm font-medium mb-1">Alasan</label><textarea name="reason" rows="3" class="w-full rounded-xl border-line">{{ old('reason', $ip->reason) }}</textarea></div>
  <div><label class="block text-sm font-medium mb-1">Expire (opsional)</label><input type="datetime-local" name="expires_at" value="{{ old('expires_at', $ip->expires_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-line"></div>
  <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $ip->exists ? $ip->is_active : true))> Aktif</label>
  <div class="flex gap-2"><button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan</button><a href="{{ route('admin.blocked-ips.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-ink font-semibold text-sm">Batal</a></div>
</form>
@endsection
