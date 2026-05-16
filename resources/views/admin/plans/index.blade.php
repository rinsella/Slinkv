@extends('layouts.admin')
@section('title','Plans')
@section('content')
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
@foreach ($plans as $p)
  <div class="bg-white rounded-2xl border border-line p-5">
    <div class="text-sm font-bold text-primary uppercase">{{ $p->name }}</div>
    <div class="mt-2 text-2xl font-bold">{{ $p->formattedPrice() }}</div>
    <div class="text-xs text-muted">{{ $p->billing_period }}</div>
    <ul class="mt-4 space-y-1 text-sm">
      <li>Max Links: <b>{{ $p->max_links ?? 'Unlimited' }}</b></li>
      <li>Max Klik/link: <b>{{ $p->max_clicks_per_link ?? 'Unlimited' }}</b></li>
      <li>Retention: <b>{{ $p->analytics_retention_days }} hari</b></li>
      <li>Bot: <b>{{ $p->bot_protection_level }}</b></li>
      <li>Geo Filter: <b>{{ $p->geo_filter_limit ?? 'Unlimited' }}</b></li>
      <li>Fallback: <b>{{ $p->has_fallback_url ? '✓' : '—' }}</b></li>
      <li>Alias: <b>{{ $p->has_custom_alias ? '✓' : '—' }}</b></li>
      <li>QR: <b>{{ $p->has_qr_code ? '✓' : '—' }}</b></li>
      <li>Export: <b>{{ $p->has_export_csv ? '✓' : '—' }}</b></li>
    </ul>
  </div>
@endforeach
</div>
@endsection
