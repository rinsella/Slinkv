@extends('layouts.admin')
@section('title','FAQs')
@section('content')
<div class="bg-white rounded-2xl border border-line overflow-hidden">
  @if ($faqs->isEmpty())<div class="p-10 text-center text-muted">Belum ada FAQ.</div>
  @else
  <ul class="divide-y divide-line">
    @foreach ($faqs as $f)
      <li class="p-4"><div class="font-semibold">{{ $f->question }}</div><div class="text-sm text-muted mt-1">{{ $f->answer }}</div></li>
    @endforeach
  </ul>
  @endif
</div>
@endsection
