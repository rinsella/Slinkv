@extends('layouts.admin')
@section('title', $article->exists ? 'Edit Artikel' : 'Artikel Baru')
@section('content')
<a href="{{ route('admin.articles.index') }}" class="text-sm text-primary">← Kembali</a>
<form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" class="mt-3 bg-white rounded-2xl border border-line p-6 max-w-4xl space-y-4">
  @csrf @if ($article->exists) @method('PUT') @endif
  <div><label class="block text-sm font-medium mb-1">Judul</label><input name="title" value="{{ old('title', $article->title) }}" required class="w-full rounded-xl border-line"></div>
  <div><label class="block text-sm font-medium mb-1">Slug <span class="text-muted text-xs">(kosong → auto)</span></label><input name="slug" value="{{ old('slug', $article->slug) }}" class="w-full rounded-xl border-line font-mono"></div>
  <div><label class="block text-sm font-medium mb-1">Excerpt</label><textarea name="excerpt" rows="2" class="w-full rounded-xl border-line">{{ old('excerpt', $article->excerpt) }}</textarea></div>
  <div><label class="block text-sm font-medium mb-1">Konten</label><textarea name="content" rows="14" required class="w-full rounded-xl border-line font-mono text-sm">{{ old('content', $article->content) }}</textarea></div>
  <div class="grid grid-cols-2 gap-3">
    <div><label class="block text-sm font-medium mb-1">Featured Image URL</label><input name="featured_image" value="{{ old('featured_image', $article->featured_image) }}" class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Status</label><select name="status" class="w-full rounded-xl border-line">@foreach (['draft','published'] as $s)<option value="{{ $s }}" @selected(old('status', $article->status)===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium mb-1">Meta Title</label><input name="meta_title" value="{{ old('meta_title', $article->meta_title) }}" class="w-full rounded-xl border-line"></div>
    <div><label class="block text-sm font-medium mb-1">Published At</label><input type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-line"></div>
    <div class="col-span-2"><label class="block text-sm font-medium mb-1">Meta Description</label><textarea name="meta_description" rows="2" class="w-full rounded-xl border-line">{{ old('meta_description', $article->meta_description) }}</textarea></div>
  </div>
  <div class="flex gap-2"><button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan</button><a href="{{ route('admin.articles.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-ink font-semibold text-sm">Batal</a></div>
</form>
@endsection
