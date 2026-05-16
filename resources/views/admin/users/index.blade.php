@extends('layouts.admin')
@section('title','Users')
@section('content')
<form method="GET" class="mb-4 flex gap-2">
  <input name="q" value="{{ request('q') }}" placeholder="Cari nama/email..." class="flex-1 max-w-md rounded-xl border-line">
  <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm">Cari</button>
</form>
<div class="bg-white rounded-2xl border border-line overflow-hidden">
  @if ($users->isEmpty())<div class="p-10 text-center text-muted">Belum ada user.</div>
  @else
  <table class="w-full text-sm">
    <thead class="bg-surface text-muted text-xs"><tr>
      <th class="text-left p-3 font-medium">Nama</th><th class="text-left p-3 font-medium">Email</th><th class="text-left p-3 font-medium">Plan</th><th class="text-center p-3 font-medium">Status</th><th class="text-left p-3 font-medium">Bergabung</th><th class="text-right p-3 font-medium"></th>
    </tr></thead>
    <tbody class="divide-y divide-line">
    @foreach ($users as $u)
      <tr>
        <td class="p-3"><a href="{{ route('admin.users.show', $u) }}" class="font-semibold text-primary hover:underline">{{ $u->name }}</a></td>
        <td class="p-3 text-muted">{{ $u->email }}</td>
        <td class="p-3 text-muted">{{ $u->plan?->name ?? 'Free' }}</td>
        <td class="p-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $u->status==='active' ? 'bg-green-100 text-green-700' : ($u->status==='suspended' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">{{ strtoupper($u->status) }}</span></td>
        <td class="p-3 text-muted">{{ $u->created_at?->format('d/m/Y') }}</td>
        <td class="p-3 text-right"><a href="{{ route('admin.users.show', $u) }}" class="text-xs text-primary font-semibold">Detail</a></td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="p-4 border-t border-line">{{ $users->links() }}</div>
  @endif
</div>
@endsection
