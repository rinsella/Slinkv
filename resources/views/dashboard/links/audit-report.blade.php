@extends('layouts.dashboard')
@section('content')
<style>
@media print {
  body { background: #fff; }
  .no-print { display: none !important; }
  .bg-white, .bg-surface { background: #fff !important; }
  .shadow-card { box-shadow: none !important; }
}
</style>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6 no-print">
  <div>
    <a href="{{ route('dashboard.links.analytics', $link) }}" class="text-sm text-primary">← Analitik link</a>
    <h1 class="mt-1 text-2xl font-bold">Audit Report</h1>
    <div class="text-sm text-muted">{{ $link->title ?: $link->slug }} · <a href="{{ $link->shortUrl() }}" class="font-mono text-primary">{{ $link->shortUrl() }}</a></div>
  </div>
  <div class="flex flex-wrap gap-2 text-sm">
    @foreach ([7=>'7 hari',30=>'30 hari',90=>'90 hari'] as $r => $label)
      <a href="?range={{ $r }}" class="px-3 py-1.5 rounded-xl {{ (int)request('range',30)===$r ? 'bg-primary text-white font-semibold' : 'bg-white border border-line' }}">{{ $label }}</a>
    @endforeach
    <button type="button" onclick="window.print()" class="px-3 py-1.5 rounded-xl bg-primary text-white font-semibold">Cetak / PDF</button>
  </div>
</div>

<div class="bg-white rounded-2xl border border-line shadow-card p-6 mb-6">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <div class="text-sm text-muted">SlinkV Security Audit Report</div>
      <div class="font-bold text-lg">{{ $link->shortUrl() }} → {{ \Illuminate\Support\Str::limit($link->destination_url, 60) }}</div>
    </div>
    <div class="text-right text-xs text-muted">
      Periode: {{ now()->subDays($days - 1)->format('d M Y') }} – {{ now()->format('d M Y') }} ({{ $days }} hari)<br>
      Dibuat: {{ now()->format('d M Y H:i') }}
    </div>
  </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
  @foreach ([
    ['Total Klik', number_format($total)],
    ['Human', number_format($human)],
    ['Bot', number_format($bot)],
    ['Bot Rate', $botRate.'%'],
    ['Unique IP', number_format($unique)],
    ['Diblokir', number_format($blocked)],
    ['Expired/Quota', number_format($expired + $quotaExceeded)],
    ['Password Prompt', number_format($passwordRequired)],
  ] as [$l, $v])
  <div class="bg-white rounded-2xl border border-line shadow-card p-4">
    <div class="text-xs text-muted">{{ $l }}</div>
    <div class="mt-1 text-xl font-bold">{{ $v }}</div>
  </div>
  @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-6">
  <div class="bg-white rounded-2xl border border-line shadow-card p-5">
    <h3 class="font-semibold mb-3">Top Bot Reasons</h3>
    @if ($topReasons->isEmpty())
      <div class="text-sm text-muted py-6 text-center">Tidak ada bot terdeteksi.</div>
    @else
      <ul class="divide-y divide-line text-sm">
        @foreach ($topReasons as $reason => $count)
          <li class="py-2 flex justify-between"><span class="font-mono">{{ $reason }}</span><span class="font-semibold">{{ $count }}</span></li>
        @endforeach
      </ul>
    @endif
  </div>
  <div class="bg-white rounded-2xl border border-line shadow-card p-5">
    <h3 class="font-semibold mb-3">Top Negara</h3>
    @if ($topCountry->isEmpty())
      <div class="text-sm text-muted py-6 text-center">Belum ada data.</div>
    @else
      <ul class="divide-y divide-line text-sm">
        @foreach ($topCountry as $r)<li class="py-2 flex justify-between"><span>{{ $r->country_name }}</span><span class="font-semibold">{{ $r->c }}</span></li>@endforeach
      </ul>
    @endif
  </div>
</div>

<div class="bg-white rounded-2xl border border-line shadow-card">
  <div class="p-5 border-b border-line"><h3 class="font-semibold">25 Klik Bot Terbaru</h3></div>
  @if ($recentSuspicious->isEmpty())
    <div class="p-10 text-center text-muted">Tidak ada aktivitas mencurigakan pada rentang ini.</div>
  @else
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-surface text-muted text-left">
          <tr><th class="px-4 py-2">Waktu</th><th class="px-4 py-2">Country</th><th class="px-4 py-2">Device</th><th class="px-4 py-2">UA</th><th class="px-4 py-2">Score</th><th class="px-4 py-2">Reasons</th><th class="px-4 py-2">Action</th></tr>
        </thead>
        <tbody class="divide-y divide-line">
          @foreach ($recentSuspicious as $c)
            <tr>
              <td class="px-4 py-2 whitespace-nowrap">{{ $c->clicked_at?->format('d/m H:i') }}</td>
              <td class="px-4 py-2">{{ $c->country_code ?: '-' }}</td>
              <td class="px-4 py-2">{{ $c->device_type ?: '-' }}</td>
              <td class="px-4 py-2 max-w-[280px] truncate" title="{{ $c->user_agent }}">{{ \Illuminate\Support\Str::limit($c->user_agent, 40) }}</td>
              <td class="px-4 py-2 font-semibold {{ $c->bot_score >= 80 ? 'text-red-600' : 'text-orange-600' }}">{{ $c->bot_score }}</td>
              <td class="px-4 py-2 text-xs font-mono">{{ is_array($c->bot_reasons) ? implode(', ', $c->bot_reasons) : '-' }}</td>
              <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs bg-red-50 text-red-700">{{ $c->action }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
