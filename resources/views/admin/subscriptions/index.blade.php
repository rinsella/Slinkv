@extends('layouts.admin')
@section('title','Subscriptions')
@section('content')
<form method="GET" class="flex flex-wrap gap-2 mb-4">
  <input name="q" value="{{ request('q') }}" placeholder="Cari email user..." class="rounded-xl border-line text-sm">
  <select name="status" class="rounded-xl border-line text-sm">
    <option value="">Semua status</option>
    @foreach (['active','pending','expired','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach
  </select>
  <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Filter</button>
</form>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($subscriptions->isEmpty())
  <div class="p-12 text-center text-muted">Belum ada subscription.</div>
@else
  <table class="w-full text-sm">
    <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">User</th><th class="text-left p-3">Plan</th><th class="text-center p-3">Status</th><th class="text-left p-3">Started</th><th class="text-left p-3">Expires</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y divide-line">
      @foreach ($subscriptions as $s)
      <tr>
        <td class="p-3">{{ $s->user?->email }}</td>
        <td class="p-3 font-semibold">{{ $s->plan?->name }}</td>
        <td class="p-3 text-center"><span class="px-2 py-0.5 rounded-full bg-slate-100 text-xs">{{ $s->status }}</span></td>
        <td class="p-3 text-muted">{{ $s->started_at?->format('d M Y') ?: '-' }}</td>
        <td class="p-3 text-muted">{{ $s->expires_at?->format('d M Y') ?: '-' }}</td>
        <td class="p-3 text-right"><a href="{{ route('admin.subscriptions.show', $s) }}" class="text-xs text-primary font-semibold">Detail</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
@endif
</div>
<div class="mt-4">{{ $subscriptions->links() }}</div>
@endsection
