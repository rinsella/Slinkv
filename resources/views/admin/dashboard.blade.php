@extends('layouts.admin')
@section('title', 'Dashboard Admin')
@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  @foreach ([
    ['Total Users', number_format($totalUsers)],
    ['Aktif', number_format($activeUsers)],
    ['Total Links', number_format($totalLinks)],
    ['Link Aktif', number_format($activeLinks)],
    ['Total Klik', number_format($totalClicks)],
    ['Bot Rate', $botRateGlobal.'%'],
    ['User Baru Hari Ini', $newUsersToday],
  ] as [$l, $v])
  <div class="bg-white rounded-2xl shadow-card border border-line p-5"><div class="text-xs text-muted">{{ $l }}</div><div class="mt-1 text-xl font-bold">{{ $v }}</div></div>
  @endforeach
</div>

<div class="bg-white rounded-2xl shadow-card border border-line p-5 mb-6">
  <h3 class="font-semibold mb-4">Klik 7 Hari Terakhir</h3>
  <div class="h-56"><canvas id="adChart"></canvas></div>
</div>

<div class="grid lg:grid-cols-2 gap-5">
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line"><h3 class="font-semibold">User Terbaru</h3></div>
    @if ($recentUsers->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($recentUsers as $u)<li class="p-3 flex justify-between"><div><div class="font-semibold">{{ $u->name }}</div><div class="text-xs text-muted">{{ $u->email }}</div></div><div class="text-xs text-muted">{{ $u->created_at?->diffForHumans() }}</div></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line"><h3 class="font-semibold">Link Terbaru</h3></div>
    @if ($recentLinks->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($recentLinks as $l)<li class="p-3 flex justify-between"><div><div class="font-mono text-primary text-xs">{{ $l->slug }}</div><div class="text-xs text-muted">{{ $l->user?->email }}</div></div><div class="text-xs">{{ $l->total_clicks }}</div></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line"><h3 class="font-semibold">Top Links</h3></div>
    @if ($topLinks->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($topLinks as $l)<li class="p-3 flex justify-between"><div><div class="font-mono text-primary text-xs">{{ $l->slug }}</div><div class="text-xs text-muted">{{ $l->user?->email }}</div></div><div class="text-xs font-semibold">{{ number_format($l->total_clicks) }}</div></li>@endforeach</ul>@endif
  </div>
</div>

@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{const el=document.getElementById('adChart'); if(!el)return; new Chart(el,{type:'line',data:{labels:@json($labels),datasets:[{label:'Klik',data:@json($clickSeries),borderColor:'#2563EB',backgroundColor:'rgba(37,99,235,.1)',fill:true,tension:.4,borderWidth:2}]},options:{plugins:{legend:{display:false}},maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});});</script>
@endpush
@endsection
