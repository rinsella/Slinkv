@extends('layouts.public')
@section('content')
<div class="mx-auto max-w-3xl px-4 sm:px-6 py-16">
  <h1 class="text-4xl font-bold mb-8">Pertanyaan yang Sering Ditanyakan</h1>
  <div class="space-y-3" x-data="{open:null}">
    @forelse ($faqs as $i => $f)
      <div class="rounded-2xl border border-line bg-white">
        <button class="w-full flex items-center justify-between p-5 text-left" x-on:click="open = open === {{ $i }} ? null : {{ $i }}">
          <span class="font-semibold">{{ $f->question }}</span>
          <svg class="w-5 h-5 transition" x-bind:class="open === {{ $i }} ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="px-5 pb-5 text-sm text-muted" x-show="open === {{ $i }}" x-collapse>{{ $f->answer }}</div>
      </div>
    @empty
      <div class="text-center py-10 text-muted">Belum ada FAQ.</div>
    @endforelse
  </div>
</div>
@endsection
