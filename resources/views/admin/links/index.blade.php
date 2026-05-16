@extends('layouts.admin')
@section('title','Short Links')
@section('content')
<form method="GET" class="mb-4 flex gap-2">
  <input name="q" value="{{ request('q') }}" placeholder="Cari slug/url..." class="flex-1 max-w-md rounded-xl border-line">
  <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm">Cari</button>
</form>
<div class="bg-white rounded-2xl border border-line overflow-hidden">
  @if ($links->isEmpty())<div class="p-10 text-center text-muted">Belum ada link.</div>
  @else
  <table class="w-full text-sm">
    <thead class="bg-surface text-muted text-xs"><tr>
      <th class="text-left p-3 font-medium">Slug</th><th class="text-left p-3 font-medium">User</th><th class="text-left p-3 font-medium">Tujuan</th><th class="text-right p-3 font-medium">Klik</th><th class="text-right p-3 font-medium">Bot %</th><th class="text-center p-3 font-medium">Status</th><th class="text-right p-3 font-medium"></th>
    </tr></thead>
    <tbody class="divide-y divide-line">
    @foreach ($links as $l)
      <tr>
        <td class="p-3 font-mono text-primary">{{ $l->slug }}</td>
        <td class="p-3 text-muted text-xs">{{ $l->user?->email }}</td>
        <td class="p-3 max-w-xs truncate text-muted">{{ $l->destination_url }}</td>
        <td class="p-3 text-right">{{ number_format($l->total_clicks) }}</td>
        <td class="p-3 text-right {{ $l->botRate() > 30 ? 'text-red-600' : 'text-muted' }}">{{ $l->botRate() }}%</td>
        <td class="p-3 text-center">@if ($l->is_active)<span class="px-2 py-0.5 rounded-full text-[10px] bg-green-100 text-green-700 font-bold">AKTIF</span>@else<span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-bold">OFF</span>@endif</td>
        <td class="p-3 text-right whitespace-nowrap">
          <form method="POST" action="{{ route('admin.links.toggle',$l) }}" class="inline">@csrf @method('PATCH')<button class="text-xs text-amber-600 font-semibold">Toggle</button></form>
          <form method="POST" action="{{ route('admin.links.destroy',$l) }}" class="inline" onsubmit="return confirm('Hapus link?')">@csrf @method('DELETE')<button class="text-xs text-red-600 font-semibold ml-2">Hapus</button></form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="p-4 border-t border-line">{{ $links->links() }}</div>
  @endif
</div>
@endsection
