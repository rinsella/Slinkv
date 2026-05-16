@extends('layouts.admin')
@section('title','Bot Logs')
@section('content')
<p class="text-sm text-muted mb-4">Klik dengan skor bot tinggi atau terdeteksi sebagai bot.</p>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($logs->isEmpty())<div class="p-12 text-center text-muted">Tidak ada aktivitas bot terdeteksi.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr>
    <th class="text-left p-3">Waktu</th>
    <th class="text-left p-3">Link</th>
    <th class="text-center p-3">Skor</th>
    <th class="text-left p-3">Klasifikasi</th>
    <th class="text-left p-3">Alasan</th>
    <th class="text-left p-3">UA</th>
    <th class="text-left p-3">Country</th>
    <th class="p-3">Aksi</th>
  </tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($logs as $log)
    @php
      $reasons = is_array($log->bot_reasons)
          ? implode(', ', $log->bot_reasons)
          : ($log->bot_reasons ?: '-');
      $score = (int) ($log->bot_score ?? 0);
      if ($log->is_bot || $score >= 70) {
          $klass = 'BOT';
          $klassColor = 'bg-red-50 text-red-700';
      } elseif ($score >= 40) {
          $klass = 'Suspicious';
          $klassColor = 'bg-amber-50 text-amber-700';
      } else {
          $klass = 'Human';
          $klassColor = 'bg-emerald-50 text-emerald-700';
      }
    @endphp
    <tr>
      <td class="p-3 text-xs">{{ optional($log->clicked_at ?? $log->created_at)->format('d/m H:i:s') }}</td>
      <td class="p-3 font-mono text-xs">{{ $log->shortLink?->slug ?? '-' }}</td>
      <td class="p-3 text-center"><span class="font-bold {{ $score >= 70 ? 'text-red-600' : ($score >= 40 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $score }}</span></td>
      <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs {{ $klassColor }}">{{ $klass }}</span></td>
      <td class="p-3 text-xs">{{ $reasons }}</td>
      <td class="p-3 text-xs text-muted">{{ \Illuminate\Support\Str::limit($log->user_agent, 50) }}</td>
      <td class="p-3 text-xs">{{ $log->country_code ?: '-' }}</td>
      <td class="p-3 text-right whitespace-nowrap">
        @if ($log->shortLink)
          <a href="{{ route('admin.links.show', $log->shortLink) }}" class="text-xs text-primary">View</a>
        @endif
        @if ($log->ip_hash)
          <form method="POST" action="{{ route('admin.bot-logs.block-ip', $log) }}" class="inline" onsubmit="return confirm('Block IP dari log ini?')">@csrf
            <button class="text-xs text-red-600 ml-2">Block IP</button>
          </form>
        @endif
        @if ($log->user_agent)
          <form method="POST" action="{{ route('admin.bot-logs.create-ua-rule', $log) }}" class="inline" onsubmit="return confirm('Buat rule user-agent dari log ini?')">@csrf
            <button class="text-xs text-indigo-600 ml-2">Add UA Rule</button>
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
