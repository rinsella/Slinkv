@extends('layouts.admin')
@section('title', 'User: '.$user->name)
@section('content')
<a href="{{ route('admin.users') }}" class="text-sm text-primary">← Daftar user</a>
<div class="grid lg:grid-cols-3 gap-5 mt-3">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-line p-6">
    <h2 class="text-xl font-bold">{{ $user->name }}</h2>
    <div class="text-sm text-muted">{{ $user->email }}</div>
    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
      <div><dt class="text-muted text-xs">Status</dt><dd class="font-semibold">{{ ucfirst($user->status) }}</dd></div>
      <div><dt class="text-muted text-xs">Role</dt><dd class="font-semibold">{{ ucfirst($user->role) }}</dd></div>
      <div><dt class="text-muted text-xs">Plan</dt><dd class="font-semibold">{{ $user->plan?->name ?? 'Free' }}</dd></div>
      <div><dt class="text-muted text-xs">Jumlah Link</dt><dd class="font-semibold">{{ $user->short_links_count }}</dd></div>
      <div><dt class="text-muted text-xs">Bergabung</dt><dd>{{ $user->created_at?->format('d M Y') }}</dd></div>
      <div><dt class="text-muted text-xs">Last Login</dt><dd>{{ $user->last_login_at?->format('d M Y H:i') ?? '—' }}</dd></div>
    </dl>
  </div>
  <div class="space-y-3">
    <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="bg-white rounded-2xl border border-line p-5">@csrf @method('PATCH')
      <button class="w-full px-4 py-2 rounded-xl {{ $user->status==='active' ? 'bg-red-600 text-white' : 'bg-green-600 text-white' }} text-sm font-semibold">{{ $user->status==='active' ? 'Suspend User' : 'Aktifkan User' }}</button>
    </form>
    <form method="POST" action="{{ route('admin.users.plan', $user) }}" class="bg-white rounded-2xl border border-line p-5 space-y-3">@csrf @method('PATCH')
      <label class="block text-sm font-medium">Ubah Paket</label>
      <select name="plan_id" class="w-full rounded-xl border-line text-sm">
        @foreach (\App\Models\Plan::orderBy('sort_order')->get() as $p)
          <option value="{{ $p->id }}" @selected($user->plan_id===$p->id)>{{ $p->name }}</option>
        @endforeach
      </select>
      <button class="w-full px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Simpan</button>
    </form>
  </div>
</div>

<div class="mt-6 bg-white rounded-2xl border border-line">
  <div class="p-4 border-b border-line"><h3 class="font-semibold">10 Pembayaran Terakhir</h3></div>
  @if ($payments->isEmpty())<div class="p-8 text-center text-muted text-sm">Belum ada pembayaran.</div>
  @else<table class="w-full text-sm"><thead class="bg-surface text-muted text-xs"><tr><th class="text-left p-3">Invoice</th><th class="text-left p-3">Tanggal</th><th class="text-right p-3">Jumlah</th><th class="text-center p-3">Status</th></tr></thead><tbody class="divide-y divide-line">@foreach ($payments as $p)<tr><td class="p-3 font-mono">{{ $p->invoice_number }}</td><td class="p-3 text-muted">{{ $p->created_at?->format('d/m/Y') }}</td><td class="p-3 text-right">Rp{{ number_format($p->amount, 0, ',', '.') }}</td><td class="p-3 text-center">{{ $p->status }}</td></tr>@endforeach</tbody></table>@endif
</div>
@endsection
