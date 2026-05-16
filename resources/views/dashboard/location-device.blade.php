@extends('layouts.dashboard')
@section('content')
<h1 class="text-2xl font-bold mb-1">Lokasi & Device</h1>
<p class="text-muted text-sm mb-6">Data 7 hari terakhir (hanya human).</p>

@php
  $hasAny = $countries->isNotEmpty() || $devices->isNotEmpty() || $browsers->isNotEmpty() || $oses->isNotEmpty() || $cities->isNotEmpty();
@endphp

@if (!$hasAny)
  <div class="bg-white rounded-2xl border border-line p-12 text-center">
    <div class="text-5xl">📍</div>
    <div class="mt-3 font-semibold">Belum ada data lokasi dan device</div>
    <div class="mt-1 text-sm text-muted">Data akan muncul setelah link Anda menerima klik.</div>
  </div>
@else
<div class="grid lg:grid-cols-3 gap-5">
  @foreach ([
    ['Top Negara', $countries, 'country_name'],
    ['Top Kota', $cities, 'city'],
    ['Device', $devices, 'device_type'],
    ['Browser', $browsers, 'browser'],
    ['OS', $oses, 'os'],
  ] as [$t, $rows, $key])
  <div class="bg-white rounded-2xl shadow-card border border-line">
    <div class="p-4 border-b border-line"><h3 class="font-semibold">{{ $t }}</h3></div>
    @if ($rows->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada.</div>
    @else<ul class="divide-y divide-line text-sm">
      @foreach ($rows as $r)<li class="p-3 flex justify-between"><span>{{ $r->$key ?: '—' }}</span><span class="font-semibold">{{ $r->c }}</span></li>@endforeach
    </ul>@endif
  </div>
  @endforeach
</div>
@endif
@endsection
