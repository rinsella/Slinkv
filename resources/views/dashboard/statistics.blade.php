@extends('layouts.dashboard')
@section('content')
<h1 class="text-2xl font-bold mb-1">Statistik</h1>
<p class="text-muted text-sm mb-6">Performa 14 hari terakhir.</p>

<div class="bg-white rounded-2xl shadow-card border border-line p-5 mb-6">
  <h3 class="font-semibold mb-4">Klik Harian - Human vs Bot</h3>
  @if (array_sum($human) + array_sum($bot) === 0)
    <div class="h-48 flex items-center justify-center text-muted text-sm">Belum ada data klik dalam 14 hari terakhir.</div>
  @else
    <div class="h-64"><canvas id="trendChart"></canvas></div>
  @endif
</div>

<div class="grid lg:grid-cols-2 gap-5">
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-5 border-b border-line"><h3 class="font-semibold">Top 10 Link</h3></div>
    @if ($topLinks->isEmpty())<div class="p-8 text-center text-muted">Belum ada data.</div>
    @else<ul class="divide-y divide-line text-sm">
      @foreach ($topLinks as $l)<li class="p-3 flex justify-between items-center"><div><div class="font-mono text-primary text-xs">{{ $l->slug }}</div><div class="text-xs text-muted truncate max-w-xs">{{ $l->title ?: $l->destination_url }}</div></div><div class="font-semibold">{{ number_format($l->total_clicks) }}</div></li>@endforeach
    </ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-5 border-b border-line"><h3 class="font-semibold">Bot Rate Tertinggi</h3></div>
    @if ($botHeavy->isEmpty())<div class="p-8 text-center text-muted">Belum ada data.</div>
    @else<ul class="divide-y divide-line text-sm">
      @foreach ($botHeavy as $l)<li class="p-3 flex justify-between items-center"><div><div class="font-mono text-primary text-xs">{{ $l->slug }}</div><div class="text-xs text-muted">{{ $l->total_clicks }} klik</div></div><div class="font-semibold text-red-600">{{ $l->botRate() }}%</div></li>@endforeach
    </ul>@endif
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('trendChart');
  if (!el) return;
  new Chart(el, { type:'bar', data:{labels:@json($labels), datasets:[{label:'Human',data:@json($human),backgroundColor:'#2563EB',stack:'s'},{label:'Bot',data:@json($bot),backgroundColor:'#EF4444',stack:'s'}]}, options:{plugins:{legend:{position:'bottom'}}, maintainAspectRatio:false, scales:{x:{stacked:true,grid:{display:false}},y:{stacked:true,beginAtZero:true,ticks:{precision:0}}}}});
});
</script>
@endpush
@endsection
