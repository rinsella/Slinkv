@extends('layouts.admin')
@section('title', $plan->exists ? 'Edit Paket: '.$plan->name : 'Paket Baru')
@section('content')
<a href="{{ route('admin.plans.index') }}" class="text-sm text-primary">← Kembali</a>
<form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="mt-3 bg-white rounded-2xl border border-line p-6 max-w-3xl space-y-4">
  @csrf @if ($plan->exists) @method('PUT') @endif
  <div class="grid grid-cols-2 gap-3">
    <div><label class="block text-sm font-medium mb-1">Nama</label><input name="name" value="{{ old('name', $plan->name) }}" required class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Slug</label><input name="slug" value="{{ old('slug', $plan->slug) }}" class="w-full rounded-xl border-line font-mono"></div>
    <div><label class="block text-sm font-medium mb-1">Harga</label><input type="number" name="price" value="{{ old('price', $plan->price ?? 0) }}" required min="0" class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Currency</label><input name="currency" value="{{ old('currency', $plan->currency ?? 'IDR') }}" required maxlength="3" class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Periode</label><select name="billing_period" class="w-full rounded-xl border-line">@foreach (['free','monthly','yearly'] as $p)<option value="{{ $p }}" @selected(old('billing_period', $plan->billing_period)===$p)>{{ ucfirst($p) }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium mb-1">Sort Order</label><input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Max Links <span class="text-muted text-xs">(kosong = unlimited)</span></label><input type="number" name="max_links" value="{{ old('max_links', $plan->max_links) }}" min="0" class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Max Klik/link <span class="text-muted text-xs">(kosong = unlimited)</span></label><input type="number" name="max_clicks_per_link" value="{{ old('max_clicks_per_link', $plan->max_clicks_per_link) }}" min="0" class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Retensi Analytics (hari)</label><input type="number" name="analytics_retention_days" value="{{ old('analytics_retention_days', $plan->analytics_retention_days ?? 30) }}" required min="1" class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Bot Protection</label><select name="bot_protection_level" class="w-full rounded-xl border-line">@foreach (['none','basic','advanced'] as $b)<option value="{{ $b }}" @selected(old('bot_protection_level', $plan->bot_protection_level)===$b)>{{ ucfirst($b) }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium mb-1">Geo Filter Limit <span class="text-muted text-xs">(kosong = unlimited)</span></label><input type="number" name="geo_filter_limit" value="{{ old('geo_filter_limit', $plan->geo_filter_limit) }}" min="0" class="w-full rounded-xl border-line"></div>
  </div>
  <div class="grid grid-cols-2 gap-3 text-sm pt-2 border-t border-line">
    @foreach (['has_fallback_url'=>'Fallback URL','has_custom_alias'=>'Custom Alias','has_qr_code'=>'QR Code','has_export_csv'=>'Export CSV','has_audit_report'=>'Audit Report','is_active'=>'Active'] as $key=>$label)
      <label class="flex items-center gap-2"><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $plan->$key))> {{ $label }}</label>
    @endforeach
  </div>
  <div class="flex gap-2"><button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan</button><a href="{{ route('admin.plans.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-ink font-semibold text-sm">Batal</a></div>
</form>
@endsection
