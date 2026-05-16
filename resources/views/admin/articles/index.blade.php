@extends('layouts.admin')
@section('title','Articles')
@section('content')
<div class="flex justify-between items-center mb-4">
  <form method="GET" class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Cari judul/slug..." class="rounded-xl border-line text-sm"><select name="status" class="rounded-xl border-line text-sm"><option value="">Semua</option>@foreach (['draft','published'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select><button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Filter</button></form>
  <a href="{{ route('admin.articles.create') }}" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">+ Artikel</a>
</div>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($articles->isEmpty())<div class="p-12 text-center text-muted">Belum ada artikel.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Judul</th><th class="text-left p-3">Slug</th><th class="text-center p-3">Status</th><th class="text-left p-3">Published</th><th class="p-3"></th></tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($articles as $a)
    <tr>
      <td class="p-3 font-semibold">{{ $a->title }}</td>
      <td class="p-3 font-mono text-xs text-muted">{{ $a->slug }}</td>
      <td class="p-3 text-center"><span class="px-2 py-0.5 rounded-full bg-slate-100 text-xs">{{ $a->status }}</span></td>
      <td class="p-3 text-xs text-muted">{{ $a->published_at?->format('d/m/Y') ?? '-' }}</td>
      <td class="p-3 text-right space-x-2">
        @if ($a->status === 'published')<form method="POST" action="{{ route('admin.articles.draft', $a) }}" class="inline">@csrf @method('PATCH')<button class="text-xs text-amber-600 font-semibold">Draft</button></form>
        @else<form method="POST" action="{{ route('admin.articles.publish', $a) }}" class="inline">@csrf @method('PATCH')<button class="text-xs text-green-700 font-semibold">Publish</button></form>@endif
        <a href="{{ route('admin.articles.edit', $a) }}" class="text-xs text-primary font-semibold">Edit</a>
        <form method="POST" action="{{ route('admin.articles.destroy', $a) }}" class="inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="text-xs text-red-600 font-semibold">×</button></form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $articles->links() }}</div>
@endsection
