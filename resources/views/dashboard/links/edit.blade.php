@extends('layouts.dashboard')
@section('content')
<div class="max-w-3xl">
  <a href="{{ route('dashboard.links.index') }}" class="text-sm text-primary">← Kembali</a>
  <h1 class="mt-2 text-2xl font-bold">Edit Shortlink</h1>
  <p class="text-sm text-muted font-mono">{{ $link->shortUrl() }}</p>

  <form method="POST" action="{{ route('dashboard.links.update', $link) }}" class="mt-6 space-y-5 bg-white rounded-2xl shadow-card border border-line p-6">
    @csrf @method('PUT')
    <div>
      <label class="block text-sm font-medium mb-1">URL Tujuan</label>
      <input type="url" name="destination_url" required value="{{ old('destination_url', $link->destination_url) }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Judul</label>
      <input name="title" value="{{ old('title', $link->title) }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>
    @if ($plan->has_fallback_url)
    <div>
      <label class="block text-sm font-medium mb-1">Fallback URL</label>
      <input type="url" name="fallback_url" value="{{ old('fallback_url', $link->fallback_url) }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
    </div>
    @endif
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Filter Device</label>
        <select name="device_filter" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
          @foreach (['all'=>'Semua','desktop'=>'Desktop','mobile'=>'Mobile','tablet'=>'Tablet'] as $k=>$v)
            <option value="{{ $k }}" @selected(old('device_filter', $link->device_filter)===$k)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Kedaluwarsa</label>
        <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $link->expires_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">
      </div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="bot_protection_enabled" value="1" {{ old('bot_protection_enabled', $link->bot_protection_enabled) ? 'checked' : '' }} class="rounded border-line text-primary focus:ring-primary"> Bot Protection</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $link->is_active) ? 'checked' : '' }} class="rounded border-line text-primary focus:ring-primary"> Link aktif</label>
    @if ($plan->geo_filter_limit !== 0)
    <div class="border-t border-line pt-5">
      <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="geo_filter_enabled" value="1" {{ old('geo_filter_enabled', $link->geo_filter_enabled) ? 'checked' : '' }} class="rounded border-line text-primary focus:ring-primary"> Geo Filter</label>
      <div class="grid sm:grid-cols-2 gap-4 mt-3">
        <div><label class="block text-xs font-medium mb-1">Allowed</label><input name="allowed_countries" value="{{ old('allowed_countries', implode(',', $link->allowed_countries ?? [])) }}" class="w-full rounded-xl border-line text-sm"></div>
        <div><label class="block text-xs font-medium mb-1">Blocked</label><input name="blocked_countries" value="{{ old('blocked_countries', implode(',', $link->blocked_countries ?? [])) }}" class="w-full rounded-xl border-line text-sm"></div>
      </div>
    </div>
    @endif
    <div class="flex gap-2">
      <button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-primary-700">Simpan Perubahan</button>
      <a href="{{ route('dashboard.links.index') }}" class="px-5 py-2.5 rounded-xl border border-line">Batal</a>
    </div>
  </form>
</div>
@endsection
