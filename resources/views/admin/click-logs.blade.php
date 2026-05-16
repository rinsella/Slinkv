@extends('layouts.admin')
@section('title','Click Logs')
@section('content')
<form method="GET" class="flex flex-wrap gap-2 mb-4">
  <input name="short_link" value="{{ request('short_link') }}" placeholder="Slug..." class="rounded-xl border-line text-sm">
  <select name="type" class="rounded-xl border-line text-sm"><option value="">Semua</option><option value="human" @selected(request('type')==='human')>Human</option><option value="bot" @selected(request('type')==='bot')>Bot</option></select>
  <input name="country" value="{{ request('country') }}" placeholder="Country code..." class="rounded-xl border-line text-sm uppercase" maxlength="2">
  <input name="source" value="{{ request('source') }}" placeholder="Source platform..." class="rounded-xl border-line text-sm">
  <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Filter</button>
</form>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($logs->isEmpty())<div class="p-12 text-center text-muted">Belum ada klik tercatat.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr>
    <th class="text-left p-3">Waktu</th>
    <th class="text-left p-3">Link</th>
    <th class="text-left p-3">Action</th>
    <th class="text-left p-3">Type</th>
    <th class="text-left p-3">Country</th>
    <th class="text-left p-3">Source</th>
    <th class="text-center p-3">Bot Score</th>
    <th class="text-left p-3">UA</th>
    <th class="p-3">Aksi</th>
  </tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($logs as $log)
    @php
      $score = (int) ($log->bot_score ?? 0);
      $action = $log->action ?: ($log->is_bot ? 'blocked' : 'redirected');
      $actionColor = match($action) {
        'redirected' => 'bg-emerald-50 text-emerald-700',
        'blocked' => 'bg-red-50 text-red-700',
        'expired' => 'bg-slate-100 text-slate-700',
        'quota_exceeded' => 'bg-amber-50 text-amber-700',
        'password_required' => 'bg-indigo-50 text-indigo-700',
        default => 'bg-slate-100',
      };
    @endphp
    <tr>
      <td class="p-3 text-xs">{{ optional($log->clicked_at ?? $log->created_at)->format('d/m H:i:s') }}</td>
      <td class="p-3 font-mono text-xs">{{ $log->shortLink?->slug ?? '-' }}</td>
      <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs {{ $actionColor }}">{{ $action }}</span></td>
      <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs {{ $log->is_bot ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $log->is_bot ? 'bot' : 'human' }}</span></td>
      <td class="p-3 text-xs">{{ $log->country_code ?: '-' }}</td>
      <td class="p-3 text-xs">{{ $log->source_platform ?: '-' }}</td>
      <td class="p-3 text-center text-xs font-semibold {{ $score >= 70 ? 'text-red-600' : ($score >= 40 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $score }}</td>
      <td class="p-3 text-xs text-muted">{{ \Illuminate\Support\Str::limit($log->user_agent, 40) }}</td>
      <td class="p-3 text-right whitespace-nowrap">
        @if ($log->shortLink)
          <a href="{{ route('admin.links.show', $log->shortLink) }}" class="text-xs text-primary">View</a>
        @endif
        @if ($log->ip_hash)
          <form method="POST" action="{{ route('admin.click-logs.block-ip', $log) }}" class="inline" onsubmit="return confirm('Block IP dari log ini?')">@csrf
            <button class="text-xs text-red-600 ml-2">Block IP</button>
          </form>
        @endif
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $logs->withQueryString()->links() }}</div>
@endsection
