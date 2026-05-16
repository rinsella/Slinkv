@extends('layouts.admin')
@section('title','Plans')
@section('content')
<div class="flex justify-between items-center mb-4">
  <p class="text-sm text-muted">Paket berlangganan. Selama mode beta, semua user otomatis mendapat akses penuh tanpa memandang paket.</p>
  <a href="{{ route('admin.plans.create') }}" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">+ Paket Baru</a>
</div>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
@forelse ($plans as $p)
  <div class="bg-white rounded-2xl border border-line p-5 flex flex-col">
    <div class="flex items-start justify-between">
      <div>
        <div class="text-sm font-bold text-primary uppercase">{{ $p->name }}</div>
        <div class="mt-2 text-2xl font-bold">{{ $p->formattedPrice() }}</div>
        <div class="text-xs text-muted">{{ $p->billing_period }}</div>
      </div>
      <span class="text-[10px] px-2 py-0.5 rounded-full {{ $p->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-muted' }}">{{ $p->is_active ? 'ACTIVE' : 'INACTIVE' }}</span>
    </div>
    <ul class="mt-4 space-y-1 text-sm flex-1">
      <li>Max Links: <b>{{ $p->max_links ?? '∞' }}</b></li>
      <li>Max Klik/link: <b>{{ $p->max_clicks_per_link ?? '∞' }}</b></li>
      <li>Retention: <b>{{ $p->analytics_retention_days }} hari</b></li>
      <li>Bot: <b>{{ $p->bot_protection_level }}</b></li>
      <li>Geo Filter: <b>{{ $p->geo_filter_limit ?? '∞' }}</b></li>
      <li>Fallback / Alias / QR / Export / Audit: <b>{{ ($p->has_fallback_url?'✓':'·').' / '.($p->has_custom_alias?'✓':'·').' / '.($p->has_qr_code?'✓':'·').' / '.($p->has_export_csv?'✓':'·').' / '.($p->has_audit_report?'✓':'·') }}</b></li>
      <li>User: <b>{{ $p->users_count ?? 0 }}</b></li>
    </ul>
    <div class="mt-4 flex gap-2">
      <a href="{{ route('admin.plans.edit', $p) }}" class="flex-1 text-center px-3 py-1.5 rounded-lg bg-slate-100 text-xs font-semibold">Edit</a>
      <form method="POST" action="{{ route('admin.plans.toggle', $p) }}">@csrf @method('PATCH')<button class="px-3 py-1.5 rounded-lg bg-amber-100 text-amber-700 text-xs font-semibold">Toggle</button></form>
      <form method="POST" action="{{ route('admin.plans.destroy', $p) }}" onsubmit="return confirm('Hapus paket?')">@csrf @method('DELETE')<button class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-semibold">×</button></form>
    </div>
  </div>
@empty
  <div class="col-span-full text-center py-12 text-muted text-sm">Belum ada paket.</div>
@endforelse
</div>
@endsection
