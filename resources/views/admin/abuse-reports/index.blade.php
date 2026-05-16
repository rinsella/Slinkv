@extends('layouts.admin')
@section('title','Abuse Reports')
@section('content')
<form method="GET" class="flex gap-2 mb-4">
  <select name="status" class="rounded-xl border-line text-sm"><option value="">Semua status</option>@foreach (['open','reviewed','closed'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
  <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Filter</button>
</form>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($reports->isEmpty())<div class="p-12 text-center text-muted">Belum ada laporan abuse.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">ID</th><th class="text-left p-3">Link</th><th class="text-left p-3">Pelapor</th><th class="text-left p-3">Alasan</th><th class="text-center p-3">Status</th><th class="text-left p-3">Tanggal</th><th class="p-3"></th></tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($reports as $r)
    <tr>
      <td class="p-3">#{{ $r->id }}</td>
      <td class="p-3 font-mono text-xs">{{ $r->shortLink?->short_code ?? '-' }}</td>
      <td class="p-3 text-xs">{{ $r->reporter_email ?: ($r->user?->email ?? 'anon') }}</td>
      <td class="p-3 text-xs">{{ \Illuminate\Support\Str::limit($r->reason, 50) }}</td>
      <td class="p-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs {{ $r->status==='open'?'bg-red-50 text-red-700':'bg-slate-100' }}">{{ $r->status }}</span></td>
      <td class="p-3 text-xs text-muted">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
      <td class="p-3 text-right"><a href="{{ route('admin.abuse-reports.show', $r) }}" class="text-xs text-primary font-semibold">Detail →</a></td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $reports->links() }}</div>
@endsection
