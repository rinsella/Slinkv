@extends('layouts.admin')
@section('title','Edit Subscription #'.$subscription->id)
@section('content')
<a href="{{ route('admin.subscriptions.show', $subscription) }}" class="text-sm text-primary">← Kembali</a>
<form method="POST" action="{{ route('admin.subscriptions.update', $subscription) }}" class="mt-3 bg-white rounded-2xl border border-line p-6 max-w-2xl space-y-4">
  @csrf @method('PUT')
  <div><label class="block text-sm font-medium mb-1">Plan</label><select name="plan_id" class="w-full rounded-xl border-line">@foreach ($plans as $p)<option value="{{ $p->id }}" @selected(old('plan_id', $subscription->plan_id)==$p->id)>{{ $p->name }}</option>@endforeach</select></div>
  <div><label class="block text-sm font-medium mb-1">Status</label><select name="status" class="w-full rounded-xl border-line">@foreach (['active','pending','expired','cancelled'] as $s)<option value="{{ $s }}" @selected(old('status', $subscription->status)===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
  <div class="grid grid-cols-2 gap-3">
    <div><label class="block text-sm font-medium mb-1">Started</label><input type="datetime-local" name="started_at" value="{{ old('started_at', $subscription->started_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Expires</label><input type="datetime-local" name="expires_at" value="{{ old('expires_at', $subscription->expires_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-line"></div>
  </div>
  <div class="flex gap-2"><button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan</button><a href="{{ route('admin.subscriptions.show', $subscription) }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-ink font-semibold text-sm">Batal</a></div>
</form>
@endsection
