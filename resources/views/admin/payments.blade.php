@extends('layouts.admin')
@section('title','Payments')
@section('content')
<div class="flex gap-2 mb-4 text-sm">
  @foreach (['' => 'Semua','pending'=>'Pending','paid'=>'Paid','expired'=>'Expired'] as $k => $v)
    <a href="?status={{ $k }}" class="px-3 py-1.5 rounded-xl {{ request('status')===$k ? 'bg-primary text-white' : 'bg-white border border-line' }}">{{ $v }}</a>
  @endforeach
</div>
<div class="bg-white rounded-2xl border border-line overflow-hidden">
  @if ($payments->isEmpty())<div class="p-10 text-center text-muted">Belum ada pembayaran.</div>
  @else
  <table class="w-full text-sm">
    <thead class="bg-surface text-muted text-xs"><tr><th class="text-left p-3">Invoice</th><th class="text-left p-3">User</th><th class="text-left p-3">Plan</th><th class="text-right p-3">Jumlah</th><th class="text-center p-3">Status</th><th class="text-left p-3">Tanggal</th><th class="text-right p-3"></th></tr></thead>
    <tbody class="divide-y divide-line">
    @foreach ($payments as $p)
      <tr>
        <td class="p-3 font-mono text-xs">{{ $p->invoice_number }}</td>
        <td class="p-3 text-xs">{{ $p->user?->email }}</td>
        <td class="p-3 text-xs">{{ $p->plan?->name }}</td>
        <td class="p-3 text-right">Rp{{ number_format($p->amount, 0, ',', '.') }}</td>
        <td class="p-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $p->status==='paid' ? 'bg-green-100 text-green-700' : ($p->status==='pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ strtoupper($p->status) }}</span></td>
        <td class="p-3 text-xs text-muted">{{ $p->created_at?->format('d/m/Y') }}</td>
        <td class="p-3 text-right">
          @if ($p->status !== 'paid')
            <form method="POST" action="{{ route('admin.payments.mark-paid', $p) }}" class="inline">@csrf @method('PATCH')<button class="text-xs text-green-700 font-semibold">Mark Paid</button></form>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="p-4 border-t border-line">{{ $payments->links() }}</div>
  @endif
</div>
@endsection
