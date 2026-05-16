@extends('layouts.admin')
@section('title','Audit Logs')
@section('content')
<form method="GET" class="flex flex-wrap gap-2 mb-4">
  <input name="action" value="{{ request('action') }}" placeholder="Action..." class="rounded-xl border-line text-sm">
  <input name="admin" value="{{ request('admin') }}" placeholder="Admin email..." class="rounded-xl border-line text-sm">
  <input name="entity_type" value="{{ request('entity_type') }}" placeholder="Entity type..." class="rounded-xl border-line text-sm">
  <input type="date" name="from" value="{{ request('from') }}" class="rounded-xl border-line text-sm">
  <input type="date" name="to" value="{{ request('to') }}" class="rounded-xl border-line text-sm">
  <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Filter</button>
</form>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($logs->isEmpty())<div class="p-12 text-center text-muted">Belum ada aktivitas tercatat.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Waktu</th><th class="text-left p-3">Admin</th><th class="text-left p-3">Action</th><th class="text-left p-3">Entity</th><th class="text-left p-3">IP</th></tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($logs as $log)
    <tr>
      <td class="p-3 text-xs">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
      <td class="p-3 text-xs">{{ $log->admin?->email ?? '-' }}</td>
      <td class="p-3"><code class="bg-slate-100 px-2 py-0.5 rounded text-xs">{{ $log->action }}</code></td>
      <td class="p-3 text-xs">{{ $log->entity_type }}{{ $log->entity_id ? '#'.$log->entity_id : '' }}</td>
      <td class="p-3 text-xs font-mono text-muted">{{ $log->ip_address ?: '-' }}</td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $logs->withQueryString()->links() }}</div>
@endsection
