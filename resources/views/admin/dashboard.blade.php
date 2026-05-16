@extends('layouts.admin')
@section('title', 'Dashboard Admin')
@section('content')

@if ($beta->isFreeAllFeatures())
<div class="mb-5 rounded-2xl border border-primary/20 bg-primary/5 p-5 flex items-start gap-4">
  <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">β</div>
  <div class="flex-1">
    <div class="font-semibold">Mode Beta Aktif - Semua fitur premium gratis untuk semua user.</div>
    <div class="text-sm text-muted mt-1">{{ $beta->announcementText() }}</div>
    @if ($beta->endsAt())<div class="text-xs text-muted mt-1">Berakhir: {{ $beta->endsAt()->format('d M Y') }}</div>@endif
  </div>
  <a href="{{ route('admin.settings') }}" class="text-sm text-primary font-semibold">Atur →</a>
</div>
@endif

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  @php
    $cards = [
      ['Total Users', number_format($totalUsers), 'admin.users.index'],
      ['User Aktif', number_format($activeUsers), 'admin.users.index'],
      ['User Suspended', number_format($suspendedUsers), 'admin.users.index'],
      ['User Baru Hari Ini', number_format($newUsersToday), null],
      ['Total Links', number_format($totalLinks), 'admin.links.index'],
      ['Link Aktif', number_format($activeLinks), 'admin.links.index'],
      ['Link Flagged', number_format($flaggedLinks), 'admin.links.index'],
      ['Active Subscriptions', number_format($activeSubs), 'admin.subscriptions.index'],
      ['Total Klik', number_format($totalClicks), null],
      ['Klik Human', number_format($humanClicks), null],
      ['Klik Bot', number_format($botClicks), 'admin.bot-logs'],
      ['Bot Rate', $botRateGlobal.'%', null],
      ['Pending Payments', number_format($pendingPayments), 'admin.payments.index'],
      ['Revenue Bulan Ini', 'Rp'.number_format($monthlyRevenue, 0, ',', '.'), 'admin.payments.index'],
      ['Open Abuse', number_format($openAbuse), 'admin.abuse-reports.index'],
      ['Pesan Belum Dibaca', number_format($unreadMessages), 'admin.contact-messages.index'],
    ];
  @endphp
  @foreach ($cards as [$label, $value, $route])
  <a @if($route) href="{{ route($route) }}" @endif class="block bg-white rounded-2xl shadow-card border border-line p-5 hover:border-primary/40 transition">
    <div class="text-xs text-muted">{{ $label }}</div>
    <div class="mt-1 text-xl font-bold">{{ $value }}</div>
  </a>
  @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-6">
  <div class="bg-white rounded-2xl shadow-card border border-line p-5">
    <h3 class="font-semibold mb-4">Klik 7 Hari Terakhir</h3>
    <div class="h-56"><canvas id="adChart7"></canvas></div>
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line p-5">
    <h3 class="font-semibold mb-4">User Baru 30 Hari Terakhir</h3>
    <div class="h-56"><canvas id="adChartUsers"></canvas></div>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-6">
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line flex justify-between"><h3 class="font-semibold">User Terbaru</h3><a href="{{ route('admin.users.index') }}" class="text-xs text-primary">Semua →</a></div>
    @if ($recentUsers->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada user.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($recentUsers as $u)<li class="p-3 flex justify-between"><div><a href="{{ route('admin.users.show', $u) }}" class="font-semibold hover:underline">{{ $u->name }}</a><div class="text-xs text-muted">{{ $u->email }}</div></div><div class="text-xs text-muted">{{ $u->created_at?->diffForHumans() }}</div></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line flex justify-between"><h3 class="font-semibold">Link Terbaru</h3><a href="{{ route('admin.links.index') }}" class="text-xs text-primary">Semua →</a></div>
    @if ($recentLinks->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada link.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($recentLinks as $l)<li class="p-3 flex justify-between"><div><a href="{{ route('admin.links.show', $l) }}" class="font-mono text-primary text-xs hover:underline">{{ $l->slug }}</a><div class="text-xs text-muted">{{ $l->user?->email }}</div></div><div class="text-xs">{{ $l->total_clicks }}</div></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line"><h3 class="font-semibold">Top Links</h3></div>
    @if ($topLinks->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada klik.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($topLinks as $l)<li class="p-3 flex justify-between"><div><a href="{{ route('admin.links.show', $l) }}" class="font-mono text-primary text-xs hover:underline">{{ $l->slug }}</a><div class="text-xs text-muted">{{ $l->user?->email }}</div></div><div class="text-xs font-semibold">{{ number_format($l->total_clicks) }}</div></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line"><h3 class="font-semibold text-red-600">High Bot Ratio (≥10 klik)</h3></div>
    @if ($highBotLinks->isEmpty())<div class="p-6 text-center text-muted text-sm">Tidak ada link mencurigakan.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($highBotLinks as $l)<li class="p-3 flex justify-between"><div><a href="{{ route('admin.links.show', $l) }}" class="font-mono text-primary text-xs hover:underline">{{ $l->slug }}</a><div class="text-xs text-muted">{{ $l->user?->email }}</div></div><div class="text-xs font-semibold text-red-600">{{ $l->total_clicks ? round($l->bot_clicks/$l->total_clicks*100, 0) : 0 }}%</div></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line flex justify-between"><h3 class="font-semibold">Pembayaran Terbaru</h3><a href="{{ route('admin.payments.index') }}" class="text-xs text-primary">Semua →</a></div>
    @if ($recentPayments->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada pembayaran.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($recentPayments as $p)<li class="p-3 flex justify-between"><div><a href="{{ route('admin.payments.show', $p) }}" class="font-mono text-primary text-xs hover:underline">{{ $p->invoice_number }}</a><div class="text-xs text-muted">{{ $p->user?->email }}</div></div><div class="text-xs"><span class="px-2 py-0.5 rounded-full bg-slate-100">{{ $p->status }}</span> Rp{{ number_format($p->amount,0,',','.') }}</div></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line flex justify-between"><h3 class="font-semibold">Laporan Abuse Terbuka</h3><a href="{{ route('admin.abuse-reports.index') }}" class="text-xs text-primary">Semua →</a></div>
    @if ($openAbuseList->isEmpty())<div class="p-6 text-center text-muted text-sm">Tidak ada laporan terbuka.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($openAbuseList as $r)<li class="p-3"><a href="{{ route('admin.abuse-reports.show', $r) }}" class="font-semibold hover:underline">{{ $r->reason ?? 'Laporan #'.$r->id }}</a><div class="text-xs text-muted">{{ $r->created_at?->diffForHumans() }}</div></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line flex justify-between"><h3 class="font-semibold">Pesan Kontak Terbaru</h3><a href="{{ route('admin.contact-messages.index') }}" class="text-xs text-primary">Semua →</a></div>
    @if ($latestMessages->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada pesan.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($latestMessages as $m)<li class="p-3"><a href="{{ route('admin.contact-messages.show', $m) }}" class="font-semibold hover:underline">{{ $m->subject ?: $m->name }}</a><div class="text-xs text-muted">{{ $m->email }}</div></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line flex justify-between"><h3 class="font-semibold">Audit Log Terbaru</h3><a href="{{ route('admin.audit-logs') }}" class="text-xs text-primary">Semua →</a></div>
    @if ($recentAudit->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada aktivitas.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($recentAudit as $a)<li class="p-3"><div class="font-semibold text-xs">{{ $a->action }}</div><div class="text-xs text-muted">{{ $a->entity_type }} · {{ $a->created_at?->diffForHumans() }}</div></li>@endforeach</ul>@endif
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('adChart7');
  if (el) new Chart(el, {
    type: 'bar',
    data: { labels: @json($labels), datasets: [
      { label: 'Human', data: @json($human), backgroundColor: '#2563EB' },
      { label: 'Bot', data: @json($bot), backgroundColor: '#EF4444' },
    ]},
    options: { plugins: { legend: { position: 'bottom' }}, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }}}}
  });
  const el2 = document.getElementById('adChartUsers');
  if (el2) new Chart(el2, {
    type: 'line',
    data: { labels: @json($userLabels), datasets: [{ label: 'User Baru', data: @json($newUsers), borderColor: '#4F46E5', backgroundColor: 'rgba(79,70,229,.1)', fill: true, tension: .35, borderWidth: 2 }]},
    options: { plugins: { legend: { display: false }}, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 }}}}
  });
});
</script>
@endpush
@endsection
