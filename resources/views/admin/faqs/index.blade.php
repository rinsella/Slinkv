@extends('layouts.admin')
@section('title','FAQs')
@section('content')
<div class="flex justify-between items-center mb-4">
  <p class="text-sm text-muted">Pertanyaan umum yang ditampilkan di halaman /faq.</p>
  <a href="{{ route('admin.faqs.create') }}" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">+ FAQ</a>
</div>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($faqs->isEmpty())<div class="p-12 text-center text-muted">Belum ada FAQ.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr><th class="text-left p-3">Order</th><th class="text-left p-3">Pertanyaan</th><th class="text-center p-3">Aktif</th><th class="p-3"></th></tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($faqs as $f)
    <tr>
      <td class="p-3 text-muted">{{ $f->sort_order }}</td>
      <td class="p-3 font-semibold">{{ $f->question }}</td>
      <td class="p-3 text-center">{{ $f->is_active ? '✓' : '-' }}</td>
      <td class="p-3 text-right space-x-2">
        <form method="POST" action="{{ route('admin.faqs.toggle', $f) }}" class="inline">@csrf @method('PATCH')<button class="text-xs text-amber-600 font-semibold">Toggle</button></form>
        <a href="{{ route('admin.faqs.edit', $f) }}" class="text-xs text-primary font-semibold">Edit</a>
        <form method="POST" action="{{ route('admin.faqs.destroy', $f) }}" class="inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="text-xs text-red-600 font-semibold">×</button></form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $faqs->links() }}</div>
@endsection
