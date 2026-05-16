@extends('layouts.admin')
@section('title','Bot Logs')
@section('content')
<div class="bg-white rounded-2xl border border-line overflow-hidden">
  @if ($logs->isEmpty())<div class="p-10 text-center text-muted">Belum ada bot terdeteksi.</div>
  @else
  <table class="w-full text-sm">
    <thead class="bg-surface text-muted text-xs"><tr><th class="text-left p-3">Waktu</th><th class="text-left p-3">Link</th><th class="text-left p-3">IP Hash</th><th class="text-left p-3">UA</th><th class="text-left p-3">Classification</th><th class="text-right p-3">Score</th></tr></thead>
    <tbody class="divide-y divide-line">
    @foreach ($logs as $l)
      <tr>
        <td class="p-3 text-xs">{{ $l->clicked_at?->format('d/m H:i:s') }}</td>
        <td class="p-3 font-mono text-primary text-xs">{{ $l->shortLink?->slug }}</td>
        <td class="p-3 font-mono text-xs text-muted">{{ substr($l->ip_hash ?: '', 0, 10) }}…</td>
        <td class="p-3 text-xs truncate max-w-xs">{{ $l->user_agent }}</td>
        <td class="p-3 text-xs">{{ $l->classification }}</td>
        <td class="p-3 text-right text-xs font-semibold text-red-600">{{ $l->bot_score }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="p-4 border-t border-line">{{ $logs->links() }}</div>
  @endif
</div>
@endsection
