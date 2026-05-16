@extends('layouts.admin')
@section('title','Articles')
@section('content')
<div class="bg-white rounded-2xl border border-line overflow-hidden">
  @if ($articles->isEmpty())<div class="p-10 text-center text-muted">Belum ada artikel.</div>
  @else
  <table class="w-full text-sm">
    <thead class="bg-surface text-muted text-xs"><tr><th class="text-left p-3">Judul</th><th class="text-left p-3">Slug</th><th class="text-center p-3">Published</th><th class="text-left p-3">Tanggal</th></tr></thead>
    <tbody class="divide-y divide-line">
    @foreach ($articles as $a)
      <tr><td class="p-3 font-semibold">{{ $a->title }}</td><td class="p-3 font-mono text-xs text-muted">{{ $a->slug }}</td><td class="p-3 text-center">{{ $a->is_published ? '✓' : '—' }}</td><td class="p-3 text-xs text-muted">{{ $a->published_at?->format('d/m/Y') ?? $a->created_at?->format('d/m/Y') }}</td></tr>
    @endforeach
    </tbody>
  </table>
  <div class="p-4 border-t border-line">{{ $articles->links() }}</div>
  @endif
</div>
@endsection
