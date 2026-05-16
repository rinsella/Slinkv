@extends('layouts.admin')
@section('title','Bot Logs')
@section('content')
<p class="text-sm text-muted mb-4">Klik dengan skor bot tinggi atau terdeteksi sebagai bot.</p>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($logs->isEmpty())<div class="p-12 text-center text-muted">Tidak ada aktivitas bot terdeteksi.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Waktu</th><th class="text-left p-3">Link</th><th class="text-center p-3">Skor</th><th class="text-left p-3">Alasan</th><th class="text-left p-3">UA</th><th class="text-left p-3">Country</th></tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($logs as $log)
    <tr>
      <td class="p-3 text-xs">{{ $log->created_at?->format('d/m H:i:s') }}</td>
      <td class="p-3 font-mono text-xs">{{ $log->shortLink?->short_code ?? '-' }}</td>
      <td class="p-3 text-center"><span class="font-bold {{ $log->bot_score >= 70 ? 'text-red-600' : 'text-amber-600' }}">{{ $log->bot_score ?? 0 }}</span></td>
      <td class="p-3 text-xs">{{ $log->bot_reason ?: ($log->is_bot ? 'detected' : '-') }}</td>
      <td class="p-3 text-xs text-muted">{{ \Illuminate\Support\Str::limit($log->user_agent, 50) }}</td>
      <td class="p-3 text-xs">{{ $log->country_code ?: '-' }}</td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $logs->withQueryString()->links() }}</div>
@endsection
