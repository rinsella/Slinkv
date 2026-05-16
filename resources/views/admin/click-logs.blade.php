@extends('layouts.admin')
@section('title','Click Logs')
@section('content')
<div class="flex gap-2 mb-4 text-sm">
  <a href="?type=" class="px-3 py-1.5 rounded-xl {{ !request('type') ? 'bg-primary text-white' : 'bg-white border border-line' }}">Semua</a>
  <a href="?type=human" class="px-3 py-1.5 rounded-xl {{ request('type')==='human' ? 'bg-primary text-white' : 'bg-white border border-line' }}">Human</a>
  <a href="?type=bot" class="px-3 py-1.5 rounded-xl {{ request('type')==='bot' ? 'bg-primary text-white' : 'bg-white border border-line' }}">Bot</a>
</div>
<div class="bg-white rounded-2xl border border-line overflow-hidden">
  @if ($logs->isEmpty())<div class="p-10 text-center text-muted">Belum ada log.</div>
  @else
  <table class="w-full text-sm">
    <thead class="bg-surface text-muted text-xs"><tr><th class="text-left p-3">Waktu</th><th class="text-left p-3">Link</th><th class="text-left p-3">Negara</th><th class="text-left p-3">Source</th><th class="text-left p-3">Device</th><th class="text-center p-3">Status</th><th class="text-right p-3">Score</th></tr></thead>
    <tbody class="divide-y divide-line">
    @foreach ($logs as $l)
      <tr>
        <td class="p-3 text-xs">{{ $l->clicked_at?->format('d/m H:i:s') }}</td>
        <td class="p-3 font-mono text-primary text-xs">{{ $l->shortLink?->slug }}</td>
        <td class="p-3 text-xs">{{ $l->country_name ?: '—' }}</td>
        <td class="p-3 text-xs">{{ $l->source_platform ?: '—' }}</td>
        <td class="p-3 text-xs">{{ $l->device_type ?: '—' }}</td>
        <td class="p-3 text-center">@if ($l->is_bot)<span class="px-2 py-0.5 rounded-full text-[10px] bg-red-100 text-red-700 font-bold">BOT</span>@else<span class="px-2 py-0.5 rounded-full text-[10px] bg-green-100 text-green-700 font-bold">HUMAN</span>@endif</td>
        <td class="p-3 text-right text-xs">{{ $l->bot_score }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="p-4 border-t border-line">{{ $logs->links() }}</div>
  @endif
</div>
@endsection
