@extends('layouts.admin')
@section('title','Subscription #'.$subscription->id)
@section('content')
<a href="{{ route('admin.subscriptions.index') }}" class="text-sm text-primary">← Daftar</a>
<div class="grid lg:grid-cols-3 gap-5 mt-3">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-line p-6">
    <h2 class="text-xl font-bold">{{ $subscription->plan?->name }} <span class="text-sm font-normal text-muted">#{{ $subscription->id }}</span></h2>
    <div class="text-sm text-muted">{{ $subscription->user?->email }}</div>
    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
      <div><dt class="text-muted text-xs">Status</dt><dd class="font-semibold">{{ $subscription->status }}</dd></div>
      <div><dt class="text-muted text-xs">Gateway</dt><dd>{{ $subscription->payment_gateway ?: '-' }}</dd></div>
      <div><dt class="text-muted text-xs">Started</dt><dd>{{ $subscription->started_at?->format('d M Y H:i') ?: '-' }}</dd></div>
      <div><dt class="text-muted text-xs">Expires</dt><dd>{{ $subscription->expires_at?->format('d M Y H:i') ?: '-' }}</dd></div>
    </dl>
  </div>
  <div class="space-y-3">
    <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="block text-center px-4 py-2 rounded-xl bg-slate-100 text-sm font-semibold">Edit</a>
    @if ($subscription->status !== 'active')<form method="POST" action="{{ route('admin.subscriptions.activate', $subscription) }}">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-semibold">Aktifkan</button></form>@endif
    @if ($subscription->status === 'active')<form method="POST" action="{{ route('admin.subscriptions.cancel', $subscription) }}">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold">Batalkan</button></form>@endif
    <form method="POST" action="{{ route('admin.subscriptions.extend', $subscription) }}" class="bg-white rounded-2xl border border-line p-4 space-y-2">@csrf @method('PATCH')
      <label class="text-sm font-medium">Perpanjang</label>
      <select name="period" class="w-full rounded-xl border-line text-sm"><option value="month">+1 Bulan</option><option value="year">+1 Tahun</option></select>
      <button class="w-full px-3 py-1.5 rounded-xl bg-primary text-white text-sm font-semibold">Extend</button>
    </form>
  </div>
</div>
<div class="mt-6 bg-white rounded-2xl border border-line">
  <div class="p-4 border-b border-line"><h3 class="font-semibold">Pembayaran Terkait</h3></div>
  @if ($payments->isEmpty())<div class="p-6 text-center text-muted text-sm">Belum ada pembayaran.</div>
  @else<table class="w-full text-sm"><thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Invoice</th><th class="text-left p-3">Tanggal</th><th class="text-right p-3">Jumlah</th><th class="text-center p-3">Status</th></tr></thead><tbody class="divide-y divide-line">@foreach ($payments as $p)<tr><td class="p-3 font-mono"><a href="{{ route('admin.payments.show', $p) }}" class="text-primary">{{ $p->invoice_number }}</a></td><td class="p-3 text-muted">{{ $p->created_at?->format('d/m/Y') }}</td><td class="p-3 text-right">Rp{{ number_format($p->amount,0,',','.') }}</td><td class="p-3 text-center">{{ $p->status }}</td></tr>@endforeach</tbody></table>@endif
</div>
@endsection
