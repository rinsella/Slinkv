@extends('layouts.dashboard')
@section('content')
<style>
@media print { .no-print { display: none !important; } body { background: #fff; } .shadow-card { box-shadow: none !important; } }
</style>
<div class="max-w-3xl">
  <div class="flex items-center justify-between mb-4 no-print">
    <a href="{{ route('dashboard.billing') }}" class="text-sm text-primary">← Riwayat Pembayaran</a>
    <button type="button" onclick="window.print()" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Cetak Invoice</button>
  </div>

  <div class="bg-white rounded-2xl border border-line shadow-card p-6 sm:p-8">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line pb-4">
      <div>
        <div class="text-2xl font-bold"><span class="brand-slink">slink</span><span class="brand-v">v</span></div>
        <div class="text-xs text-muted mt-1">Invoice</div>
      </div>
      <div class="text-right text-sm">
        <div class="font-mono font-semibold">{{ $payment->invoice_number }}</div>
        <div class="text-muted">{{ $payment->created_at->format('d M Y, H:i') }}</div>
        <div class="mt-1">
          @php
            $colors = ['pending'=>'bg-yellow-100 text-yellow-800','paid'=>'bg-green-100 text-green-800','failed'=>'bg-red-100 text-red-800','expired'=>'bg-slate-200 text-slate-700','refunded'=>'bg-blue-100 text-blue-800'];
            $c = $colors[$payment->status] ?? 'bg-slate-100 text-slate-700';
          @endphp
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $c }}">{{ strtoupper($payment->status) }}</span>
        </div>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mt-5 text-sm">
      <div>
        <div class="text-muted text-xs">Tagihan untuk</div>
        <div class="font-semibold">{{ $payment->user?->name ?? auth()->user()->name }}</div>
        <div class="text-muted">{{ $payment->user?->email ?? auth()->user()->email }}</div>
      </div>
      <div>
        <div class="text-muted text-xs">Jatuh tempo</div>
        <div class="font-semibold">{{ $payment->expired_at?->format('d M Y, H:i') ?? '-' }}</div>
        @if ($payment->paid_at)
          <div class="text-green-700 text-xs">Dibayar {{ $payment->paid_at->format('d M Y, H:i') }}</div>
        @endif
      </div>
    </div>

    <table class="w-full text-sm mt-6">
      <thead class="bg-surface text-muted text-xs uppercase">
        <tr><th class="text-left p-3">Item</th><th class="text-right p-3">Harga</th></tr>
      </thead>
      <tbody class="divide-y divide-line">
        <tr>
          <td class="p-3">
            <div class="font-semibold">Paket {{ $payment->plan?->name ?? 'Subscription' }}</div>
            <div class="text-xs text-muted">{{ $payment->plan?->billing_period ?? 'Bulanan' }}</div>
          </td>
          <td class="p-3 text-right font-mono">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
        </tr>
      </tbody>
      <tfoot>
        <tr class="bg-surface"><td class="p-3 text-right font-semibold">Total</td><td class="p-3 text-right font-mono font-bold">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }} {{ $payment->currency ?? 'IDR' }}</td></tr>
      </tfoot>
    </table>

    @if ($payment->status === 'pending')
      <div class="mt-6 rounded-xl border border-line bg-surface p-4">
        <div class="text-sm font-semibold mb-2">Cara Pembayaran (Manual Transfer)</div>
        <div class="text-sm">Transfer ke rekening berikut, lalu konfirmasi via menu kontak:</div>
        <ul class="mt-2 text-sm font-mono">
          <li>Bank: <span class="font-semibold">{{ $bankBank }}</span></li>
          <li>No. Rekening: <span class="font-semibold">{{ $bankNumber }}</span></li>
          <li>Atas Nama: <span class="font-semibold">{{ $bankName }}</span></li>
          <li>Jumlah: <span class="font-semibold">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</span></li>
          <li>Berita: <span class="font-semibold">{{ $payment->invoice_number }}</span></li>
        </ul>
        <p class="text-xs text-muted mt-2">Admin akan memverifikasi pembayaran Anda secara manual.</p>
      </div>
    @endif

    <div class="mt-6 text-xs text-muted text-center">
      Invoice ini dibuat otomatis oleh sistem SlinkV. Hubungi support jika ada pertanyaan.
    </div>
  </div>
</div>
@endsection
