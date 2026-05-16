@extends('layouts.admin')
@section('title', 'Edit Link: '.$link->slug)
@section('content')
<a href="{{ route('admin.links.show', $link) }}" class="text-sm text-primary">← Kembali</a>
<form method="POST" action="{{ route('admin.links.update', $link) }}" class="mt-3 bg-white rounded-2xl border border-line p-6 max-w-2xl space-y-4">
  @csrf @method('PUT')
  <div><label class="block text-sm font-medium mb-1">Judul</label><input name="title" value="{{ old('title', $link->title) }}" class="w-full rounded-xl border-line"></div>
  <div><label class="block text-sm font-medium mb-1">Slug</label><input name="slug" value="{{ old('slug', $link->slug) }}" required pattern="[A-Za-z0-9_-]+" maxlength="32" class="w-full rounded-xl border-line font-mono"></div>
  <div><label class="block text-sm font-medium mb-1">Destination URL</label><input type="url" name="destination_url" value="{{ old('destination_url', $link->destination_url) }}" required class="w-full rounded-xl border-line"></div>
  <div><label class="block text-sm font-medium mb-1">Fallback URL</label><input type="url" name="fallback_url" value="{{ old('fallback_url', $link->fallback_url) }}" class="w-full rounded-xl border-line"></div>
  <div class="grid grid-cols-2 gap-3">
    <div><label class="block text-sm font-medium mb-1">Device Filter</label><select name="device_filter" class="w-full rounded-xl border-line">@foreach (['all','desktop','mobile','tablet'] as $d)<option value="{{ $d }}" @selected(old('device_filter', $link->device_filter)===$d)>{{ ucfirst($d) }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium mb-1">Expires</label><input type="date" name="expires_at" value="{{ old('expires_at', $link->expires_at?->format('Y-m-d')) }}" class="w-full rounded-xl border-line"></div>
  </div>
  <div class="grid grid-cols-2 gap-3 text-sm">
    <label class="flex items-center gap-2"><input type="hidden" name="bot_protection_enabled" value="0"><input type="checkbox" name="bot_protection_enabled" value="1" @checked(old('bot_protection_enabled', $link->bot_protection_enabled))> Bot Protection</label>
    <label class="flex items-center gap-2"><input type="hidden" name="geo_filter_enabled" value="0"><input type="checkbox" name="geo_filter_enabled" value="1" @checked(old('geo_filter_enabled', $link->geo_filter_enabled))> Geo Filter</label>
    <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $link->is_active))> Aktif</label>
    <label class="flex items-center gap-2"><input type="hidden" name="is_flagged" value="0"><input type="checkbox" name="is_flagged" value="1" @checked(old('is_flagged', $link->is_flagged))> Flagged</label>
  </div>
  <div class="flex gap-2"><button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan</button><a href="{{ route('admin.links.show', $link) }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-ink font-semibold text-sm">Batal</a></div>
</form>
@endsection
