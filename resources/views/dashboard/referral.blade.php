@extends('layouts.dashboard')
@section('content')
@php $code = auth()->user()->referral_code; $url = url('/?ref='.$code); @endphp
<h1 class="text-2xl font-bold mb-1">Program Referral</h1>
<p class="text-muted text-sm mb-6">Ajak teman menggunakan SlinkV.</p>

<div class="bg-white rounded-2xl shadow-card border border-line p-6 mb-6">
  <div class="text-sm text-muted mb-2">Kode Referral Anda</div>
  <div class="flex flex-col sm:flex-row gap-3">
    <input readonly value="{{ $url }}" class="flex-1 rounded-xl border-line bg-surface font-mono text-sm">
    <button type="button" onclick="navigator.clipboard.writeText('{{ $url }}'); this.textContent='Tersalin'" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Salin Link</button>
  </div>
</div>

<div class="bg-white rounded-2xl shadow-card border border-line">
  <div class="p-5 border-b border-line"><h3 class="font-semibold">Pengguna yang Anda Ajak</h3></div>
  @if ($referrals->isEmpty())
    <div class="p-10 text-center text-muted"><div class="text-4xl">🎁</div><div class="mt-2 font-semibold text-ink">Belum ada referral</div><div class="text-sm mt-1">Bagikan link di atas untuk mulai mendapatkan referral.</div></div>
  @else
    <table class="w-full text-sm">
      <thead class="bg-surface text-muted text-xs"><tr><th class="text-left p-3 font-medium">Nama</th><th class="text-left p-3 font-medium">Email</th><th class="text-left p-3 font-medium">Bergabung</th></tr></thead>
      <tbody class="divide-y divide-line">
        @foreach ($referrals as $r)<tr><td class="p-3">{{ $r->name }}</td><td class="p-3 text-muted">{{ $r->email }}</td><td class="p-3 text-muted">{{ $r->created_at?->format('d/m/Y') }}</td></tr>@endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
