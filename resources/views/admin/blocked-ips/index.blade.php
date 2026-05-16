@extends('layouts.admin')
@section('title','Blocked IPs')
@section('content')
<div class="flex justify-between items-center mb-4">
  <p class="text-sm text-muted">Plain IP tidak disimpan - hanya hash SHA256 untuk privacy.</p>
  <a href="{{ route('admin.blocked-ips.create') }}" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">+ Blokir IP</a>
</div>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($ips->isEmpty())<div class="p-12 text-center text-muted">Belum ada IP diblokir.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">IP Hash</th><th class="text-left p-3">Alasan</th><th class="text-center p-3">Aktif</th><th class="text-left p-3">Expire</th><th class="p-3"></th></tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($ips as $ip)
    <tr>
      <td class="p-3 font-mono text-xs">{{ \Illuminate\Support\Str::limit($ip->ip_hash, 24) }}</td>
      <td class="p-3">{{ $ip->reason ?: '-' }}</td>
      <td class="p-3 text-center">{{ $ip->is_active ? '✓' : '-' }}</td>
      <td class="p-3 text-xs text-muted">{{ $ip->expires_at?->format('d/m/Y') ?? '∞' }}</td>
      <td class="p-3 text-right space-x-2">
        <form method="POST" action="{{ route('admin.blocked-ips.toggle', $ip) }}" class="inline">@csrf @method('PATCH')<button class="text-xs text-amber-600 font-semibold">Toggle</button></form>
        <a href="{{ route('admin.blocked-ips.edit', $ip) }}" class="text-xs text-primary font-semibold">Edit</a>
        <form method="POST" action="{{ route('admin.blocked-ips.destroy', $ip) }}" class="inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="text-xs text-red-600 font-semibold">×</button></form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $ips->links() }}</div>
@endsection
