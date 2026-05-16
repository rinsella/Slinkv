@extends('layouts.admin')
@section('title','Payments')
@section('content')
<form method="GET" class="flex flex-wrap gap-2 mb-4">
  <input name="q" value="{{ request('q') }}" placeholder="Cari invoice / email..." class="rounded-xl border-line text-sm">
  <select name="status" class="rounded-xl border-line text-sm"><option value="">Semua</option>@foreach (['pending','paid','failed','expired','refunded'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
  <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Filter</button>
</form>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($payments->isEmpty())
  <div class="p-12 text-center text-muted">Belum ada pembayaran.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Invoice</th><th class="text-left p-3">User</th><th class="text-left p-3">Plan</th><th class="text-right p-3">Jumlah</th><th class="text-center p-3">Status</th><th class="text-left p-3">Dibuat</th><th class="p-3"></th></tr></thead>
  <tbody class="divide-y divide-line">
    @foreach ($payments as $p)
    <tr>
      <td class="p-3 font-mono"><a href="{{ route('admin.payments.show', $p) }}" class="text-primary hover:underline">{{ $p->invoice_number }}</a></td>
      <td class="p-3">{{ $p->user?->email }}</td>
      <td class="p-3">{{ $p->plan?->name }}</td>
      <td class="p-3 text-right">Rp{{ number_format($p->amount,0,',','.') }}</td>
      <td class="p-3 text-center"><span class="px-2 py-0.5 rounded-full bg-slate-100 text-xs">{{ $p->status }}</span></td>
      <td class="p-3 text-muted">{{ $p->created_at?->format('d/m/Y') }}</td>
      <td class="p-3 text-right">
        @if ($p->status === 'pending')<form method="POST" action="{{ route('admin.payments.mark-paid', $p) }}" class="inline">@csrf @method('PATCH')<button class="text-xs text-green-700 font-semibold">Mark Paid</button></form>@endif
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $payments->links() }}</div>
@endsection
