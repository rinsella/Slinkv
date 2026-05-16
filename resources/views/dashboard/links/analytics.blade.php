@extends('layouts.dashboard')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <a href="{{ route('dashboard.links.index') }}" class="text-sm text-primary">← Daftar link</a>
    <h1 class="mt-1 text-2xl font-bold">{{ $link->title ?: $link->slug }}</h1>
    <a href="{{ $link->shortUrl() }}" target="_blank" class="text-sm font-mono text-primary hover:underline">{{ $link->shortUrl() }}</a>
  </div>
  <div class="flex gap-2 text-sm">
    @foreach ([1=>'24 jam',7=>'7 hari',30=>'30 hari',90=>'90 hari'] as $r => $label)
      <a href="?range={{ $r }}" class="px-3 py-1.5 rounded-xl {{ (int)request('range',7)===$r ? 'bg-primary text-white font-semibold' : 'bg-white border border-line' }}">{{ $label }}</a>
    @endforeach
  </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
  @foreach ([
    ['Total Klik', number_format($total), 'text-ink'],
    ['Human', number_format($human), 'text-primary'],
    ['Bot', number_format($bot), 'text-red-600'],
    ['Unique IP', number_format($unique), 'text-ink'],
    ['Bot Rate', $botRate.'%', $botRate > 30 ? 'text-red-600' : 'text-green-600'],
  ] as [$l,$v,$c])
  <div class="bg-white rounded-2xl shadow-card border border-line p-4"><div class="text-xs text-muted">{{ $l }}</div><div class="mt-1 text-xl font-bold {{ $c }}">{{ $v }}</div></div>
  @endforeach
</div>

<div class="bg-white rounded-2xl shadow-card border border-line p-5 mb-6">
  <h3 class="font-semibold mb-4">Trend Human vs Bot</h3>
  @if ($total === 0)
    <div class="h-48 flex items-center justify-center text-muted text-sm">Belum ada klik pada rentang ini.</div>
  @else
    <div class="h-64"><canvas id="trendChart"></canvas></div>
  @endif
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-6">
  @foreach ([
    ['Top Negara', $topCountry, 'country_name'],
    ['Top Source', $topSource, 'source_platform'],
    ['Top Device', $topDevice, 'device_type'],
  ] as [$title, $rows, $key])
  <div class="bg-white rounded-2xl shadow-card border border-line p-5">
    <h3 class="font-semibold mb-3">{{ $title }}</h3>
    @if ($rows->isEmpty())
      <div class="text-sm text-muted py-6 text-center">Belum ada data.</div>
    @else
      <ul class="divide-y divide-line text-sm">
        @foreach ($rows as $r)<li class="py-2 flex justify-between"><span>{{ $r->$key ?: '—' }}</span><span class="font-semibold">{{ $r->c }}</span></li>@endforeach
      </ul>
    @endif
  </div>
  @endforeach
</div>

<div class="bg-white rounded-2xl shadow-card border border-line">
  <div class="p-5 border-b border-line"><h3 class="font-semibold">20 Klik Terakhir</h3></div>
  @if ($recent->isEmpty())
    <div class="p-10 text-center text-muted">Belum ada klik tercatat.</div>
  @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-surface text-muted text-xs"><tr>
          <th class="text-left p-3 font-medium">Waktu</th><th class="text-left p-3 font-medium">Negara</th><th class="text-left p-3 font-medium">Source</th><th class="text-left p-3 font-medium">Device</th><th class="text-left p-3 font-medium">Status</th>
        </tr></thead>
        <tbody class="divide-y divide-line">
        @foreach ($recent as $c)
          <tr>
            <td class="p-3">{{ $c->clicked_at?->format('d/m H:i:s') }}</td>
            <td class="p-3">{{ $c->country_name ?: '—' }}</td>
            <td class="p-3">{{ $c->source_platform ?: '—' }}</td>
            <td class="p-3">{{ $c->device_type ?: '—' }}</td>
            <td class="p-3">@if ($c->is_bot)<span class="px-2 py-0.5 rounded-full text-[10px] bg-red-100 text-red-700 font-bold">BOT</span>@else<span class="px-2 py-0.5 rounded-full text-[10px] bg-green-100 text-green-700 font-bold">HUMAN</span>@endif</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('trendChart');
  if (!el) return;
  new Chart(el, { type:'bar', data:{labels:@json($labels), datasets:[{label:'Human',data:@json($humanSeries),backgroundColor:'#2563EB',stack:'s'},{label:'Bot',data:@json($botSeries),backgroundColor:'#EF4444',stack:'s'}]}, options:{plugins:{legend:{position:'bottom'}}, maintainAspectRatio:false, scales:{x:{stacked:true,grid:{display:false}},y:{stacked:true,beginAtZero:true,ticks:{precision:0}}}}});
});
</script>
@endpush
@endsection
