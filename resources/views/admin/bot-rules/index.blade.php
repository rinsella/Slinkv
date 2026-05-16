@extends('layouts.admin')
@section('title','Bot Rules')
@section('content')
<div class="flex justify-between items-center mb-4">
  <p class="text-sm text-muted">Aturan deteksi bot. Skor digabung di redirect untuk klasifikasi bot vs manusia.</p>
  <a href="{{ route('admin.bot-rules.create') }}" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">+ Rule</a>
</div>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($rules->isEmpty())<div class="p-12 text-center text-muted">Belum ada rule.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Nama</th><th class="text-left p-3">Tipe</th><th class="text-left p-3">Pattern</th><th class="text-center p-3">Skor</th><th class="text-center p-3">Aktif</th><th class="p-3"></th></tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($rules as $r)
    <tr>
      <td class="p-3 font-semibold">{{ $r->name }}</td>
      <td class="p-3 text-xs"><code class="bg-slate-100 px-2 py-0.5 rounded">{{ $r->type }}</code></td>
      <td class="p-3 font-mono text-xs">{{ \Illuminate\Support\Str::limit($r->pattern, 40) }}</td>
      <td class="p-3 text-center font-bold">{{ $r->score }}</td>
      <td class="p-3 text-center">{{ $r->is_active ? '✓' : '-' }}</td>
      <td class="p-3 text-right space-x-2">
        <form method="POST" action="{{ route('admin.bot-rules.toggle', $r) }}" class="inline">@csrf @method('PATCH')<button class="text-xs text-amber-600 font-semibold">Toggle</button></form>
        <a href="{{ route('admin.bot-rules.edit', $r) }}" class="text-xs text-primary font-semibold">Edit</a>
        <form method="POST" action="{{ route('admin.bot-rules.destroy', $r) }}" class="inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="text-xs text-red-600 font-semibold">×</button></form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $rules->links() }}</div>
@endsection
