@extends('layouts.admin')
@section('title','Click Logs')
@section('content')
<form method="GET" class="flex flex-wrap gap-2 mb-4">
  <input name="short_link" value="{{ request('short_link') }}" placeholder="Short code..." class="rounded-xl border-line text-sm">
  <select name="type" class="rounded-xl border-line text-sm"><option value="">Semua</option><option value="human" @selected(request('type')==='human')>Human</option><option value="bot" @selected(request('type')==='bot')>Bot</option></select>
  <input name="country" value="{{ request('country') }}" placeholder="Country code..." class="rounded-xl border-line text-sm uppercase" maxlength="2">
  <input name="source" value="{{ request('source') }}" placeholder="Source..." class="rounded-xl border-line text-sm">
  <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Filter</button>
</form>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($logs->isEmpty())<div class="p-12 text-center text-muted">Belum ada klik tercatat.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Waktu</th><th class="text-left p-3">Link</th><th class="text-left p-3">Type</th><th class="text-left p-3">Country</th><th class="text-left p-3">Source</th><th class="text-left p-3">UA</th></tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($logs as $log)
    <tr>
      <td class="p-3 text-xs">{{ $log->created_at?->format('d/m H:i:s') }}</td>
      <td class="p-3 font-mono text-xs">{{ $log->shortLink?->short_code ?? '-' }}</td>
      <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs {{ $log->is_bot ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $log->is_bot ? 'bot' : 'human' }}</span></td>
      <td class="p-3 text-xs">{{ $log->country_code ?: '-' }}</td>
      <td class="p-3 text-xs">{{ $log->source ?: '-' }}</td>
      <td class="p-3 text-xs text-muted">{{ \Illuminate\Support\Str::limit($log->user_agent, 40) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $logs->withQueryString()->links() }}</div>
@endsection
