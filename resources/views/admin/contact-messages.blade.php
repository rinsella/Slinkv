@extends('layouts.admin')
@section('title','Contact Messages')
@section('content')
<div class="bg-white rounded-2xl border border-line overflow-hidden">
  @if ($messages->isEmpty())<div class="p-10 text-center text-muted">Belum ada pesan masuk.</div>
  @else
  <table class="w-full text-sm">
    <thead class="bg-surface text-muted text-xs"><tr><th class="text-left p-3">Dari</th><th class="text-left p-3">Subjek</th><th class="text-left p-3">Pesan</th><th class="text-left p-3">Tanggal</th></tr></thead>
    <tbody class="divide-y divide-line">
    @foreach ($messages as $m)
      <tr><td class="p-3"><div class="font-semibold">{{ $m->name }}</div><div class="text-xs text-muted">{{ $m->email }}</div></td><td class="p-3">{{ $m->subject }}</td><td class="p-3 max-w-md truncate text-muted">{{ $m->message }}</td><td class="p-3 text-xs text-muted">{{ $m->created_at?->format('d/m/Y H:i') }}</td></tr>
    @endforeach
    </tbody>
  </table>
  <div class="p-4 border-t border-line">{{ $messages->links() }}</div>
  @endif
</div>
@endsection
