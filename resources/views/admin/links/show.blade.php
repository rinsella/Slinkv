@extends('layouts.admin')
@section('title', 'Link: '.$link->slug)
@section('content')
<a href="{{ route('admin.links.index') }}" class="text-sm text-primary">← Daftar link</a>
<div class="grid lg:grid-cols-3 gap-5 mt-3">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-line p-6">
    <div class="flex items-start justify-between">
      <div>
        <div class="font-mono text-lg text-primary">{{ $link->slug }}</div>
        <a href="{{ $link->destination_url }}" target="_blank" class="text-sm text-muted break-all hover:underline">{{ $link->destination_url }}</a>
      </div>
      <a href="{{ route('admin.links.edit', $link) }}" class="px-4 py-2 rounded-xl bg-slate-100 text-sm font-semibold">Edit</a>
    </div>
    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
      <div><dt class="text-muted text-xs">Pemilik</dt><dd class="font-semibold">{{ $link->user?->email ?? '-' }}</dd></div>
      <div><dt class="text-muted text-xs">Status</dt><dd>{{ $link->is_active ? 'Aktif' : 'Nonaktif' }} {{ $link->is_flagged ? '· FLAGGED' : '' }}</dd></div>
      <div><dt class="text-muted text-xs">Total Klik</dt><dd class="font-semibold">{{ number_format($link->total_clicks) }}</dd></div>
      <div><dt class="text-muted text-xs">Klik Human / Bot</dt><dd>{{ number_format($link->human_clicks) }} / {{ number_format($link->bot_clicks) }}</dd></div>
      <div><dt class="text-muted text-xs">Bot Protection</dt><dd>{{ $link->bot_protection_enabled ? 'Aktif' : 'Mati' }}</dd></div>
      <div><dt class="text-muted text-xs">Device Filter</dt><dd>{{ $link->device_filter }}</dd></div>
      <div><dt class="text-muted text-xs">Dibuat</dt><dd>{{ $link->created_at?->format('d M Y H:i') }}</dd></div>
      <div><dt class="text-muted text-xs">Expires</dt><dd>{{ $link->expires_at?->format('d M Y') ?? '-' }}</dd></div>
    </dl>
  </div>
  <div class="space-y-3">
    <form method="POST" action="{{ route('admin.links.toggle', $link) }}" class="bg-white rounded-2xl border border-line p-5">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-amber-500 text-white text-sm font-semibold">Toggle Aktif</button></form>
    @if ($link->is_flagged)
      <form method="POST" action="{{ route('admin.links.unflag', $link) }}" class="bg-white rounded-2xl border border-line p-5">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-semibold">Hapus Flag</button></form>
    @else
      <form method="POST" action="{{ route('admin.links.flag', $link) }}" class="bg-white rounded-2xl border border-line p-5">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold">Tandai Suspicious</button></form>
    @endif
    <form method="POST" action="{{ route('admin.links.destroy', $link) }}" class="bg-white rounded-2xl border border-line p-5" onsubmit="return confirm('Hapus link permanen?')">@csrf @method('DELETE')<button class="w-full px-4 py-2 rounded-xl bg-slate-200 text-red-700 text-sm font-semibold">Hapus Permanen</button></form>
  </div>
</div>

<div class="mt-6 grid lg:grid-cols-3 gap-5">
  <div class="bg-white rounded-2xl border border-line">
    <div class="p-4 border-b border-line"><h3 class="font-semibold">Top Negara</h3></div>
    @if ($topCountries->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada data.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($topCountries as $c)<li class="p-3 flex justify-between"><span>{{ $c->country_code ?: '-' }}</span><span class="font-semibold">{{ $c->c }}</span></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl border border-line">
    <div class="p-4 border-b border-line"><h3 class="font-semibold">Top Sumber</h3></div>
    @if ($topSources->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada data.</div>
    @else<ul class="divide-y divide-line text-sm">@foreach ($topSources as $s)<li class="p-3 flex justify-between"><span>{{ $s->source_platform ?: 'direct' }}</span><span class="font-semibold">{{ $s->c }}</span></li>@endforeach</ul>@endif
  </div>
  <div class="bg-white rounded-2xl border border-line">
    <div class="p-4 border-b border-line"><h3 class="font-semibold">30 Klik Terakhir</h3></div>
    @if ($recentLogs->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada klik.</div>
    @else<ul class="divide-y divide-line text-xs max-h-96 overflow-y-auto">@foreach ($recentLogs as $log)<li class="p-2.5"><div class="flex justify-between"><span class="font-mono">{{ $log->country_code ?: '-' }} · {{ $log->device_type ?: '-' }}</span><span class="{{ $log->is_bot ? 'text-red-600' : 'text-green-600' }} font-semibold">{{ $log->is_bot ? 'BOT' : 'HUMAN' }}</span></div><div class="text-muted">{{ $log->clicked_at?->format('d/m H:i:s') }}</div></li>@endforeach</ul>@endif
  </div>
</div>
@endsection
