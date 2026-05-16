@extends('layouts.public')
@section('content')
<div class="mx-auto max-w-2xl px-4 sm:px-6 py-16">
  <h1 class="text-4xl font-bold">Hubungi Kami</h1>
  <p class="mt-2 text-muted">Kirim pertanyaan, saran, atau permintaan kerja sama.</p>
  @if (session('success'))<div class="mt-6 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif
  <form method="POST" action="{{ route('contact.store') }}" class="mt-8 space-y-4">
    @csrf
    <div><label class="block text-sm font-medium mb-1">Nama</label><input name="name" required value="{{ old('name') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
    <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" required value="{{ old('email') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
    <div><label class="block text-sm font-medium mb-1">Subjek</label><input name="subject" required value="{{ old('subject') }}" class="w-full rounded-xl border-line focus:ring-primary focus:border-primary"></div>
    <div><label class="block text-sm font-medium mb-1">Pesan</label><textarea name="message" rows="6" required class="w-full rounded-xl border-line focus:ring-primary focus:border-primary">{{ old('message') }}</textarea></div>
    @error('name')<div class="text-sm text-red-600">{{ $message }}</div>@enderror
    @error('email')<div class="text-sm text-red-600">{{ $message }}</div>@enderror
    @error('subject')<div class="text-sm text-red-600">{{ $message }}</div>@enderror
    @error('message')<div class="text-sm text-red-600">{{ $message }}</div>@enderror
    <button class="px-5 py-3 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-700">Kirim Pesan</button>
  </form>
</div>
@endsection
