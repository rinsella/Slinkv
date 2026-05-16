@extends('layouts.admin')
@section('title','Contact Messages')
@section('content')
<form method="GET" class="flex flex-wrap gap-2 mb-4">
  <input name="q" value="{{ request('q') }}" placeholder="Cari nama/email/subjek..." class="rounded-xl border-line text-sm">
  <select name="status" class="rounded-xl border-line text-sm"><option value="">Semua</option>@foreach (['unread','read','replied'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
  <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Filter</button>
</form>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($messages->isEmpty())
  <div class="p-12 text-center text-muted">Belum ada pesan.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Dari</th><th class="text-left p-3">Subjek</th><th class="text-center p-3">Status</th><th class="text-left p-3">Tanggal</th><th class="p-3"></th></tr></thead>
  <tbody class="divide-y divide-line">
    @foreach ($messages as $m)
    <tr class="{{ $m->status === 'unread' ? 'font-semibold' : '' }}">
      <td class="p-3"><div>{{ $m->name }}</div><div class="text-xs text-muted font-normal">{{ $m->email }}</div></td>
      <td class="p-3"><a href="{{ route('admin.contact-messages.show', $m) }}" class="text-primary hover:underline">{{ $m->subject ?: '(tanpa subjek)' }}</a></td>
      <td class="p-3 text-center"><span class="px-2 py-0.5 rounded-full bg-slate-100 text-xs font-normal">{{ $m->status }}</span></td>
      <td class="p-3 text-muted font-normal">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
      <td class="p-3 text-right"><a href="{{ route('admin.contact-messages.show', $m) }}" class="text-xs text-primary font-semibold">Detail</a></td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $messages->links() }}</div>
@endsection
