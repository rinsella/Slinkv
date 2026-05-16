@extends('layouts.dashboard')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="text-muted text-sm">Ringkasan performa link Anda.</p>
  </div>
  <a href="{{ route('dashboard.links.create') }}" class="px-4 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-700">+ Buat Short Link</a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  @foreach ([
    ['Total Link', $totalLinks, 'text-ink'],
    ['Link Aktif', $activeLinks, 'text-primary'],
    ['Total Klik', number_format($totalClicks), 'text-ink'],
    ['Bot Rate', $botRate.'%', $botRate > 30 ? 'text-red-600' : 'text-green-600'],
  ] as [$l,$v,$c])
  <div class="bg-white rounded-2xl shadow-card border border-line p-5">
    <div class="text-xs text-muted">{{ $l }}</div>
    <div class="mt-2 text-2xl font-bold {{ $c }}">{{ $v }}</div>
  </div>
  @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-6">
  <div class="lg:col-span-2 bg-white rounded-2xl shadow-card border border-line p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold">Klik 7 Hari Terakhir</h3>
      <a href="{{ route('dashboard.statistics') }}" class="text-xs text-primary hover:underline">Detail →</a>
    </div>
    @if (array_sum($clicks7Days) === 0)
      <div class="h-48 flex flex-col items-center justify-center text-center text-muted text-sm">
        <div class="text-4xl">📊</div>
        <div class="mt-2">Belum ada klik. Buat dan sebar shortlink Anda untuk mulai melihat data.</div>
      </div>
    @else
      <div class="h-48"><canvas id="chart7"></canvas></div>
    @endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line p-5">
    <h3 class="font-semibold mb-4">Insight</h3>
    <div class="space-y-3 text-sm">
      <div class="flex justify-between"><span class="text-muted">Human</span><span class="font-semibold text-primary">{{ number_format($humanClicks) }}</span></div>
      <div class="flex justify-between"><span class="text-muted">Bot</span><span class="font-semibold text-red-600">{{ number_format($botClicks) }}</span></div>
      <div class="flex justify-between"><span class="text-muted">Top Source</span><span class="font-semibold">{{ $topSource ?: '—' }}</span></div>
      <div class="flex justify-between"><span class="text-muted">Top Negara</span><span class="font-semibold">{{ $topCountry ?: '—' }}</span></div>
    </div>
  </div>
</div>

<div class="bg-white rounded-2xl shadow-card border border-line p-5 mb-6">
  <div class="flex items-center justify-between mb-4">
    <h3 class="font-semibold">Aktivitas 24 Jam (Human vs Bot)</h3>
  </div>
  @if (array_sum($hourlyHuman) + array_sum($hourlyBot) === 0)
    <div class="h-40 flex items-center justify-center text-muted text-sm">Belum ada aktivitas dalam 24 jam terakhir.</div>
  @else
    <div class="h-40"><canvas id="chartHourly"></canvas></div>
  @endif
</div>

<div class="bg-white rounded-2xl shadow-card border border-line">
  <div class="flex items-center justify-between p-5 border-b border-line">
    <h3 class="font-semibold">Link Terbaru</h3>
    <a href="{{ route('dashboard.links.index') }}" class="text-xs text-primary hover:underline">Lihat semua →</a>
  </div>
  @if ($recent->isEmpty())
    <div class="p-10 text-center text-muted">
      <div class="text-4xl">🔗</div>
      <div class="mt-2 font-semibold text-ink">Belum ada shortlink</div>
      <div class="text-sm mt-1">Klik tombol di atas untuk membuat link pertama Anda.</div>
      <a href="{{ route('dashboard.links.create') }}" class="mt-4 inline-block px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">+ Buat Shortlink</a>
    </div>
  @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-surface text-muted text-xs"><tr>
          <th class="text-left p-3 font-medium">Slug</th><th class="text-left p-3 font-medium">Tujuan</th>
          <th class="text-right p-3 font-medium">Klik</th><th class="text-right p-3 font-medium">Bot</th><th class="text-right p-3 font-medium">Aksi</th>
        </tr></thead>
        <tbody class="divide-y divide-line">
          @foreach ($recent as $r)
          <tr>
            <td class="p-3 font-mono text-primary">{{ $r->slug }}</td>
            <td class="p-3 max-w-xs truncate text-muted">{{ $r->destination_url }}</td>
            <td class="p-3 text-right">{{ number_format($r->total_clicks) }}</td>
            <td class="p-3 text-right text-red-600">{{ number_format($r->bot_clicks) }}</td>
            <td class="p-3 text-right"><a href="{{ route('dashboard.links.analytics', $r) }}" class="text-primary text-xs">Analytics</a></td>
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
  const c1 = document.getElementById('chart7');
  if (c1) new Chart(c1, { type:'line', data:{labels:@json($labels7Days), datasets:[{label:'Klik', data:@json($clicks7Days), borderColor:'#2563EB', backgroundColor:'rgba(37,99,235,.1)', fill:true, tension:.4, borderWidth:2}]}, options:{plugins:{legend:{display:false}}, maintainAspectRatio:false, scales:{y:{beginAtZero:true,ticks:{precision:0}}}} });
  const c2 = document.getElementById('chartHourly');
  if (c2) new Chart(c2, { type:'bar', data:{labels:@json($hourlyLabels), datasets:[{label:'Human',data:@json($hourlyHuman), backgroundColor:'#2563EB', stack:'s'},{label:'Bot',data:@json($hourlyBot),backgroundColor:'#EF4444', stack:'s'}]}, options:{plugins:{legend:{position:'bottom'}}, maintainAspectRatio:false, scales:{x:{stacked:true,grid:{display:false}},y:{stacked:true,beginAtZero:true,ticks:{precision:0}}}} });
});
</script>
@endpush
@endsection
