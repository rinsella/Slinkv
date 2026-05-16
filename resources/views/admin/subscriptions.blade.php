@extends('layouts.admin')
@section('title','Subscriptions')
@section('content')
<div class="bg-white rounded-2xl border border-line overflow-hidden">
  @if ($subs->isEmpty())<div class="p-10 text-center text-muted">Belum ada subscription.</div>
  @else
  <table class="w-full text-sm">
    <thead class="bg-surface text-muted text-xs"><tr><th class="text-left p-3">User</th><th class="text-left p-3">Plan</th><th class="text-center p-3">Status</th><th class="text-left p-3">Mulai</th><th class="text-left p-3">Berakhir</th></tr></thead>
    <tbody class="divide-y divide-line">
    @foreach ($subs as $s)
      <tr><td class="p-3">{{ $s->user?->email }}</td><td class="p-3">{{ $s->plan?->name }}</td><td class="p-3 text-center">{{ $s->status }}</td><td class="p-3 text-muted">{{ $s->started_at?->format('d/m/Y') }}</td><td class="p-3 text-muted">{{ $s->expires_at?->format('d/m/Y') }}</td></tr>
    @endforeach
    </tbody>
  </table>
  <div class="p-4 border-t border-line">{{ $subs->links() }}</div>
  @endif
</div>
@endsection
