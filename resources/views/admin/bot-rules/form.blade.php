@extends('layouts.admin')
@section('title', $rule->exists ? 'Edit Rule' : 'Bot Rule Baru')
@section('content')
<a href="{{ route('admin.bot-rules.index') }}" class="text-sm text-primary">← Kembali</a>
<form method="POST" action="{{ $rule->exists ? route('admin.bot-rules.update', $rule) : route('admin.bot-rules.store') }}" class="mt-3 bg-white rounded-2xl border border-line p-6 max-w-2xl space-y-4">
  @csrf @if ($rule->exists) @method('PUT') @endif
  <div><label class="block text-sm font-medium mb-1">Nama</label><input name="name" value="{{ old('name', $rule->name) }}" required class="w-full rounded-xl border-line"></div>
  <div class="grid grid-cols-2 gap-3">
    <div><label class="block text-sm font-medium mb-1">Tipe</label><select name="type" class="w-full rounded-xl border-line">@foreach (['user_agent_contains','ip_rate','header_missing','country','referer','custom'] as $t)<option value="{{ $t }}" @selected(old('type', $rule->type)===$t)>{{ $t }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium mb-1">Skor (0-100)</label><input type="number" min="0" max="100" name="score" value="{{ old('score', $rule->score ?? 50) }}" required class="w-full rounded-xl border-line"></div>
  </div>
  <div><label class="block text-sm font-medium mb-1">Pattern</label><input name="pattern" value="{{ old('pattern', $rule->pattern) }}" required class="w-full rounded-xl border-line font-mono"><p class="text-xs text-muted mt-1">Untuk user_agent_contains: substring. Untuk ip_rate: angka per menit. Untuk country: kode ISO 2 huruf.</p></div>
  <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule->exists ? $rule->is_active : true))> Aktif</label>
  <div class="flex gap-2"><button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan</button><a href="{{ route('admin.bot-rules.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-ink font-semibold text-sm">Batal</a></div>
</form>
@endsection
