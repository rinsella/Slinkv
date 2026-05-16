@extends('layouts.public')
@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
  <h1 class="text-4xl font-bold mb-8">Artikel & Insight</h1>
  @if ($articles->isEmpty())
    <div class="text-center py-20 text-muted">Belum ada artikel.</div>
  @else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach ($articles as $a)
        <a href="{{ route('articles.show', $a->slug) }}" class="block rounded-2xl border border-line bg-white p-5 hover:shadow-card transition">
          <div class="text-xs text-muted">{{ $a->published_at?->translatedFormat('d M Y') }}</div>
          <div class="mt-2 text-lg font-semibold">{{ $a->title }}</div>
          <p class="mt-2 text-sm text-muted line-clamp-3">{{ $a->excerpt }}</p>
          <div class="mt-3 text-primary text-sm font-semibold">Baca →</div>
        </a>
      @endforeach
    </div>
    <div class="mt-8">{{ $articles->links() }}</div>
  @endif
</div>
@endsection
