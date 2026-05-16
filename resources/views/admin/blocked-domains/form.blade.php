@extends('layouts.admin')
@section('title', $domain->exists ? 'Edit Domain' : 'Blokir Domain')
@section('content')
<a href="{{ route('admin.blocked-domains.index') }}" class="text-sm text-primary">← Kembali</a>
<form method="POST" action="{{ $domain->exists ? route('admin.blocked-domains.update', $domain) : route('admin.blocked-domains.store') }}" class="mt-3 bg-white rounded-2xl border border-line p-6 max-w-xl space-y-4">
  @csrf @if ($domain->exists) @method('PUT') @endif
  <div><label class="block text-sm font-medium mb-1">Domain</label><input name="domain" value="{{ old('domain', $domain->domain) }}" required placeholder="contoh.com" class="w-full rounded-xl border-line font-mono"><p class="text-xs text-muted mt-1">Otomatis dinormalisasi (lowercase, tanpa http://).</p></div>
  <div><label class="block text-sm font-medium mb-1">Alasan</label><textarea name="reason" rows="3" class="w-full rounded-xl border-line">{{ old('reason', $domain->reason) }}</textarea></div>
  <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $domain->exists ? $domain->is_active : true))> Aktif</label>
  <div class="flex gap-2"><button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan</button><a href="{{ route('admin.blocked-domains.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-ink font-semibold text-sm">Batal</a></div>
</form>
@endsection
