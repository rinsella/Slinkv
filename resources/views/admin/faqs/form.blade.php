@extends('layouts.admin')
@section('title', $faq->exists ? 'Edit FAQ' : 'FAQ Baru')
@section('content')
<a href="{{ route('admin.faqs.index') }}" class="text-sm text-primary">← Kembali</a>
<form method="POST" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}" class="mt-3 bg-white rounded-2xl border border-line p-6 max-w-2xl space-y-4">
  @csrf @if ($faq->exists) @method('PUT') @endif
  <div><label class="block text-sm font-medium mb-1">Pertanyaan</label><input name="question" value="{{ old('question', $faq->question) }}" required class="w-full rounded-xl border-line"></div>
  <div><label class="block text-sm font-medium mb-1">Jawaban</label><textarea name="answer" rows="6" required class="w-full rounded-xl border-line">{{ old('answer', $faq->answer) }}</textarea></div>
  <div class="grid grid-cols-2 gap-3">
    <div><label class="block text-sm font-medium mb-1">Sort Order</label><input type="number" name="sort_order" value="{{ old('sort_order', $faq->sort_order ?? 0) }}" class="w-full rounded-xl border-line"></div>
    <label class="flex items-end gap-2 pb-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq->is_active))> Aktif</label>
  </div>
  <div class="flex gap-2"><button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan</button><a href="{{ route('admin.faqs.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-ink font-semibold text-sm">Batal</a></div>
</form>
@endsection
