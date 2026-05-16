@extends('layouts.dashboard')
@section('content')
<div class="max-w-4xl">
  <h1 class="text-2xl font-bold">Billing</h1>
  <p class="text-muted mt-1 text-sm">Status langganan dan riwayat pembayaran Anda.</p>

  @if (session('success'))
    <div class="mt-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 border border-green-200 text-sm">{{ session('success') }}</div>
  @endif
  @if ($errors->any())
    <div class="mt-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 border border-red-200 text-sm">{{ $errors->first() }}</div>
  @endif

  @if ($beta)
    <div class="mt-6 rounded-2xl bg-gradient-to-br from-primary to-secondary text-white p-8 shadow-card">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-[10px] font-bold uppercase tracking-wider">Beta</div>
      <div class="mt-3 text-3xl font-extrabold">100% Gratis</div>
      <p class="mt-2 text-white/85 text-sm">Selama tahap beta, seluruh fitur SlinkV tersedia gratis untuk akun Anda - tanpa batasan link, klik, atau fitur premium. Checkout berbayar dinonaktifkan sementara.</p>
    </div>
  @else
    <div class="mt-6 grid sm:grid-cols-2 gap-4">
      <div class="bg-white border border-line rounded-2xl p-6 shadow-card">
        <div class="text-xs text-muted uppercase font-semibold">Paket Saat Ini</div>
        <div class="mt-2 text-xl font-bold">{{ $plan?->name ?? 'Free' }}</div>
        @if ($sub)
          <div class="text-sm text-muted mt-1">Aktif sampai {{ $sub->expires_at?->format('d M Y') ?? '—' }}</div>
        @else
          <div class="text-sm text-muted mt-1">Tidak ada subscription aktif.</div>
        @endif
      </div>
      <div class="bg-white border border-line rounded-2xl p-6 shadow-card">
        <div class="text-xs text-muted uppercase font-semibold">Total Tagihan Terbayar</div>
        <div class="mt-2 text-xl font-bold">Rp {{ number_format((int) $invoices->where('status','paid')->sum('amount'), 0, ',', '.') }}</div>
        <div class="text-xs text-muted mt-1">{{ $invoices->where('status','paid')->count() }} invoice paid</div>
      </div>
    </div>
  @endif

  <div class="mt-8">
    <h2 class="text-lg font-semibold mb-3">Pilih Paket</h2>
    @if ($beta)
      <p class="text-sm text-muted mb-4">Checkout berbayar akan diaktifkan setelah masa beta selesai.</p>
    @endif
    <div class="grid sm:grid-cols-3 gap-4">
      @foreach ($plans as $p)
        <div class="bg-white border border-line rounded-2xl p-5 shadow-card flex flex-col">
          <div class="text-sm font-semibold">{{ $p->name }}</div>
          <div class="mt-1 text-2xl font-bold">
            @if ((int) $p->price === 0) Gratis @else Rp{{ number_format((int) $p->price, 0, ',', '.') }} @endif
          </div>
          <div class="text-xs text-muted">{{ $p->billing_period ?? 'bulanan' }}</div>
          <ul class="mt-3 text-xs text-muted space-y-1 flex-1">
            <li>• {{ $p->max_links ? $p->max_links . ' link aktif' : 'Unlimited link' }}</li>
            <li>• {{ $p->max_clicks_per_link ? number_format($p->max_clicks_per_link) . ' klik/link/bln' : 'Unlimited klik' }}</li>
            @if ($p->has_custom_alias) <li>• Custom alias</li> @endif
            @if ($p->has_qr_code) <li>• QR Code</li> @endif
            @if ($p->has_export_csv) <li>• Export CSV</li> @endif
          </ul>
          <form method="POST" action="{{ route('dashboard.billing.checkout', $p) }}" class="mt-4">
            @csrf
            <button @disabled($beta || (int)$p->price === 0)
              class="w-full px-4 py-2 rounded-xl text-sm font-semibold {{ $beta || (int)$p->price === 0 ? 'bg-slate-100 text-muted cursor-not-allowed' : 'bg-primary text-white hover:bg-primary-700' }}">
              {{ $beta ? 'Tersedia setelah beta' : ((int)$p->price === 0 ? 'Paket Gratis' : 'Berlangganan') }}
            </button>
          </form>
        </div>
      @endforeach
    </div>
  </div>

  <div class="mt-8 bg-white border border-line rounded-2xl shadow-card overflow-hidden">
    <div class="p-5 border-b border-line"><h2 class="font-semibold">Riwayat Invoice</h2></div>
    @if ($invoices->isEmpty())
      <div class="p-10 text-center text-muted text-sm">Belum ada riwayat pembayaran.</div>
    @else
      <table class="w-full text-sm">
        <thead class="bg-surface text-muted text-xs uppercase">
          <tr><th class="text-left p-3">No. Invoice</th><th class="text-left p-3">Tanggal</th><th class="text-left p-3">Paket</th><th class="text-right p-3">Jumlah</th><th class="text-center p-3">Status</th><th class="p-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-line">
          @foreach ($invoices as $inv)
            <tr>
              <td class="p-3 font-mono">{{ $inv->invoice_number }}</td>
              <td class="p-3 text-muted">{{ $inv->created_at->format('d/m/Y') }}</td>
              <td class="p-3">{{ $inv->plan?->name ?? '-' }}</td>
              <td class="p-3 text-right font-mono">Rp {{ number_format((int)$inv->amount, 0, ',', '.') }}</td>
              <td class="p-3 text-center">
                @php
                  $colors = ['pending'=>'bg-yellow-100 text-yellow-800','paid'=>'bg-green-100 text-green-800','failed'=>'bg-red-100 text-red-800','expired'=>'bg-slate-200 text-slate-700','refunded'=>'bg-blue-100 text-blue-800'];
                  $cl = $colors[$inv->status] ?? 'bg-slate-100 text-slate-700';
                @endphp
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $cl }}">{{ strtoupper($inv->status) }}</span>
              </td>
              <td class="p-3 text-right"><a href="{{ route('dashboard.billing.invoice', $inv) }}" class="text-xs text-primary font-semibold">Lihat</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>
@endsection
