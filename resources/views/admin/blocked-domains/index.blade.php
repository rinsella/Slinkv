@extends('layouts.admin')
@section('title','Blocked Domains')
@section('content')
<div class="flex justify-between items-center mb-4">
  <form method="GET" class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Cari domain..." class="rounded-xl border-line text-sm"><button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Cari</button></form>
  <a href="{{ route('admin.blocked-domains.create') }}" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">+ Blokir Domain</a>
</div>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($domains->isEmpty())<div class="p-12 text-center text-muted">Belum ada domain diblokir.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Domain</th><th class="text-left p-3">Alasan</th><th class="text-center p-3">Aktif</th><th class="text-left p-3">Ditambah</th><th class="p-3"></th></tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($domains as $d)
    <tr>
      <td class="p-3 font-mono">{{ $d->domain }}</td>
      <td class="p-3">{{ $d->reason ?: '-' }}</td>
      <td class="p-3 text-center">{{ $d->is_active ? '✓' : '-' }}</td>
      <td class="p-3 text-xs text-muted">{{ $d->created_at?->format('d/m/Y') }}</td>
      <td class="p-3 text-right space-x-2">
        <form method="POST" action="{{ route('admin.blocked-domains.toggle', $d) }}" class="inline">@csrf @method('PATCH')<button class="text-xs text-amber-600 font-semibold">Toggle</button></form>
        <a href="{{ route('admin.blocked-domains.edit', $d) }}" class="text-xs text-primary font-semibold">Edit</a>
        <form method="POST" action="{{ route('admin.blocked-domains.destroy', $d) }}" class="inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="text-xs text-red-600 font-semibold">×</button></form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $domains->links() }}</div>
@endsection
