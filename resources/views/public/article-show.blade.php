@extends('layouts.public', ['title' => $article->meta_title ?: $article->title, 'description' => $article->meta_description ?: $article->excerpt])
@section('content')
<article class="mx-auto max-w-3xl px-4 sm:px-6 py-16">
  <a href="{{ route('articles') }}" class="text-sm text-primary hover:underline">← Semua artikel</a>
  <h1 class="mt-4 text-4xl font-bold">{{ $article->title }}</h1>
  <div class="mt-2 text-sm text-muted">{{ $article->published_at?->translatedFormat('d M Y') }}</div>
  <div class="prose prose-slate mt-8 max-w-none">{!! $article->content !!}</div>
</article>
@endsection
