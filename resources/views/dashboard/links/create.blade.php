@extends('layouts.dashboard')
@section('content')
<div class="max-w-3xl">
  <a href="{{ route('dashboard.links.index') }}" class="text-sm text-primary">← Kembali ke daftar</a>
  <h1 class="mt-2 text-2xl font-bold">Buat Shortlink Baru</h1>
  <p class="text-sm text-muted">Paket {{ $plan->name }}{{ $plan->max_links ? ' - maks '.$plan->max_links.' link' : '' }}.</p>
  @if (!empty($features['isBetaFree']))
    <div class="mt-3 px-3 py-2 rounded-xl bg-primary/10 text-primary text-xs font-medium border border-primary/20">Beta aktif: semua fitur premium tersedia gratis.</div>
  @endif

  <form method="POST" action="{{ route('dashboard.links.store') }}" class="mt-6 space-y-5 bg-white rounded-2xl shadow-card border border-line p-6">
    @csrf
    <div>
      <label class="block text-sm font-medium mb-1">URL Tujuan <span class="text-red-600">*</span></label>
      <input type="url" name="destination_url" required value="{{ old('destination_url', $prefill) }}" placeholder="https://contoh.com/halaman" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Judul (opsional)</label>
      <input name="title" value="{{ old('title') }}" maxlength="200" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>
    @if (!empty($features['canUseCustomAlias']))
    <div>
      <label class="block text-sm font-medium mb-1">Custom Alias (opsional)</label>
      <div class="flex">
        <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-line bg-surface text-sm text-muted">{{ url('/') }}/</span>
        <input name="custom_alias" value="{{ old('custom_alias') }}" pattern="[a-zA-Z0-9_-]+" minlength="3" maxlength="32" placeholder="contoh: promo-juli" class="flex-1 rounded-r-xl border-line focus:ring-primary focus:border-primary">
      </div>
      <div class="text-xs text-muted mt-1">Hanya huruf, angka, _ dan -</div>
    </div>
    @endif
    @if (!empty($features['canUseFallback']))
    <div>
      <label class="block text-sm font-medium mb-1">Fallback URL (untuk bot/quota/expired)</label>
      <input type="url" name="fallback_url" value="{{ old('fallback_url') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>
    @endif

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Filter Device</label>
        <select name="device_filter" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
          @foreach (['all'=>'Semua','desktop'=>'Desktop','mobile'=>'Mobile','tablet'=>'Tablet'] as $k=>$v)
            <option value="{{ $k }}" @selected(old('device_filter','all')===$k)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Kedaluwarsa (opsional)</label>
        <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
      </div>
    </div>

    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="bot_protection_enabled" value="1" {{ old('bot_protection_enabled', '1') ? 'checked' : '' }} class="rounded border-line text-primary focus:ring-primary"> Aktifkan Bot Protection</label>

    @if (!empty($features['canUseGeoFilter']))
    <div class="border-t border-line pt-5">
      <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="geo_filter_enabled" value="1" {{ old('geo_filter_enabled') ? 'checked' : '' }} class="rounded border-line text-primary focus:ring-primary"> Aktifkan Geo Filter</label>
      <div class="grid sm:grid-cols-2 gap-4 mt-3">
        <div>
          <label class="block text-xs font-medium mb-1">Allowed Countries (kode ISO 2 huruf, pisah koma)</label>
          <input name="allowed_countries" value="{{ old('allowed_countries') }}" placeholder="ID,MY,SG" class="w-full rounded-xl border-line text-sm focus:ring-primary focus:border-primary">
        </div>
        <div>
          <label class="block text-xs font-medium mb-1">Blocked Countries</label>
          <input name="blocked_countries" value="{{ old('blocked_countries') }}" placeholder="CN,RU" class="w-full rounded-xl border-line text-sm focus:ring-primary focus:border-primary">
        </div>
      </div>
      <div class="text-xs text-muted mt-2">@if ($features['geoFilterLimit'] === null)Geo filter unlimited.@else Maksimal {{ $features['geoFilterLimit'] }} negara di Allowed.@endif</div>
    </div>
    @endif

    <div>
      <label class="block text-sm font-medium mb-1">Password (opsional)</label>
      <input type="password" name="password" minlength="4" maxlength="64" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>

    <div class="flex gap-2">
      <button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-primary-700">Simpan</button>
      <a href="{{ route('dashboard.links.index') }}" class="px-5 py-2.5 rounded-xl border border-line">Batal</a>
    </div>
  </form>
</div>
@endsection
