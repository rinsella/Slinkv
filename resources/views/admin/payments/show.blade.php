@extends('layouts.admin')
@section('title','Payment '.$payment->invoice_number)
@section('content')
<a href="{{ route('admin.payments.index') }}" class="text-sm text-primary">← Daftar</a>
<div class="grid lg:grid-cols-3 gap-5 mt-3">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-line p-6">
    <div class="flex items-start justify-between">
      <div>
        <div class="text-xs text-muted">Invoice</div>
        <div class="font-mono text-lg">{{ $payment->invoice_number }}</div>
      </div>
      <span class="px-2 py-0.5 rounded-full bg-slate-100 text-xs uppercase">{{ $payment->status }}</span>
    </div>
    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
      <div><dt class="text-muted text-xs">User</dt><dd>{{ $payment->user?->email }}</dd></div>
      <div><dt class="text-muted text-xs">Plan</dt><dd>{{ $payment->plan?->name }}</dd></div>
      <div><dt class="text-muted text-xs">Jumlah</dt><dd class="font-bold">Rp{{ number_format($payment->amount,0,',','.') }} {{ $payment->currency }}</dd></div>
      <div><dt class="text-muted text-xs">Gateway</dt><dd>{{ $payment->gateway ?: '-' }}</dd></div>
      <div><dt class="text-muted text-xs">Reference</dt><dd class="font-mono text-xs">{{ $payment->gateway_reference ?: '-' }}</dd></div>
      <div><dt class="text-muted text-xs">Subscription</dt><dd>@if ($payment->subscription)<a href="{{ route('admin.subscriptions.show', $payment->subscription) }}" class="text-primary">#{{ $payment->subscription->id }}</a>@else - @endif</dd></div>
      <div><dt class="text-muted text-xs">Dibayar</dt><dd>{{ $payment->paid_at?->format('d M Y H:i') ?? '-' }}</dd></div>
      <div><dt class="text-muted text-xs">Expired</dt><dd>{{ $payment->expired_at?->format('d M Y H:i') ?? '-' }}</dd></div>
    </dl>
  </div>
  <div class="space-y-3">
    @if ($payment->status === 'pending')
      <form method="POST" action="{{ route('admin.payments.mark-paid', $payment) }}">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-semibold">Mark Paid</button></form>
      <form method="POST" action="{{ route('admin.payments.mark-failed', $payment) }}">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold">Mark Failed</button></form>
      <form method="POST" action="{{ route('admin.payments.mark-expired', $payment) }}">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-slate-200 text-ink text-sm font-semibold">Mark Expired</button></form>
    @endif
    @if ($payment->status === 'paid')
      <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" onsubmit="return confirm('Refund pembayaran ini?')">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-amber-600 text-white text-sm font-semibold">Refund</button></form>
    @endif
  </div>
</div>
@endsection
