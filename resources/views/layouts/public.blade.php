@extends('layouts.base')

@section('body')
<header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-line">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
    <a href="{{ route('home') }}" class="flex items-center gap-2 text-2xl">
      <span class="brand-slink">slink</span><span class="brand-v">v</span>
    </a>
    <nav class="hidden lg:flex items-center gap-7 text-sm text-muted font-medium">
      <a href="{{ route('solutions') }}" class="hover:text-ink">Solusi</a>
      <a href="{{ route('how-it-works') }}" class="hover:text-ink">Cara Kerja</a>
      <a href="{{ route('articles') }}" class="hover:text-ink">Artikel</a>
      <a href="{{ route('faq') }}" class="hover:text-ink">FAQ</a>
      <a href="{{ route('about') }}" class="hover:text-ink">Tentang</a>
      <a href="{{ route('contact') }}" class="hover:text-ink">Kontak</a>
    </nav>
    <div class="flex items-center gap-3">
      @auth
        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard.index') }}" class="hidden sm:inline px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-700">Dashboard</a>
      @else
        <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium text-ink hover:text-primary">Masuk</a>
        <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-700">Daftar Gratis</a>
      @endauth
      <button class="lg:hidden p-2 -mr-2 text-ink" x-data x-on:click="document.getElementById('mob-nav').classList.toggle('hidden')" aria-label="Menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </div>
  <div id="mob-nav" class="lg:hidden hidden border-t border-line bg-white">
    <div class="px-4 py-3 flex flex-col gap-3 text-sm">
      <a href="{{ route('solutions') }}" class="py-2">Solusi</a>
      <a href="{{ route('how-it-works') }}" class="py-2">Cara Kerja</a>
      <a href="{{ route('articles') }}" class="py-2">Artikel</a>
      <a href="{{ route('faq') }}" class="py-2">FAQ</a>
      <a href="{{ route('about') }}" class="py-2">Tentang</a>
      <a href="{{ route('contact') }}" class="py-2">Kontak</a>
      @guest <a href="{{ route('login') }}" class="py-2 font-semibold">Masuk</a> @endguest
    </div>
  </div>
</header>

<main>@yield('content')</main>

<footer class="mt-20 bg-white border-t border-line">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-2 md:grid-cols-4 gap-8 text-sm">
    <div class="col-span-2 md:col-span-1">
      <a href="{{ route('home') }}" class="flex items-center gap-2 text-2xl mb-3">
        <span class="brand-slink">slink</span><span class="brand-v">v</span>
      </a>
      <p class="text-muted">Link Pendek. Traffic Bersih. Analytics Real-time.</p>
    </div>
    <div>
      <div class="font-semibold mb-3">Produk</div>
      <ul class="space-y-2 text-muted">
        <li><a href="{{ route('solutions') }}" class="hover:text-ink">Solusi</a></li>
        <li><a href="{{ route('how-it-works') }}" class="hover:text-ink">Cara Kerja</a></li>
      </ul>
    </div>
    <div>
      <div class="font-semibold mb-3">Sumber Daya</div>
      <ul class="space-y-2 text-muted">
        <li><a href="{{ route('articles') }}" class="hover:text-ink">Artikel</a></li>
        <li><a href="{{ route('faq') }}" class="hover:text-ink">FAQ</a></li>
        <li><a href="{{ route('contact') }}" class="hover:text-ink">Kontak</a></li>
        <li><a href="{{ route('abuse') }}" class="hover:text-ink">Laporkan Penyalahgunaan</a></li>
      </ul>
    </div>
    <div>
      <div class="font-semibold mb-3">Legal</div>
      <ul class="space-y-2 text-muted">
        <li><a href="{{ route('terms') }}" class="hover:text-ink">Syarat</a></li>
        <li><a href="{{ route('privacy') }}" class="hover:text-ink">Privasi</a></li>
        <li><a href="{{ route('aup') }}" class="hover:text-ink">AUP</a></li>
      </ul>
    </div>
  </div>
  <div class="border-t border-line py-5 text-center text-xs text-muted">© {{ date('Y') }} {{ $siteName ?? 'SlinkV' }}. Semua hak dilindungi.</div>
</footer>

<a href="https://wa.me/{{ $supportWa ?? '6281234567890' }}" target="_blank" rel="noopener" class="fixed bottom-5 right-5 z-50 inline-flex items-center gap-2 px-4 py-3 rounded-full bg-[#25D366] text-white shadow-lg hover:scale-105 transition" aria-label="WhatsApp">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 3.5A11 11 0 0 0 3.6 17.7L2 22l4.4-1.5A11 11 0 1 0 20.5 3.5Zm-8.4 17a8.9 8.9 0 0 1-4.6-1.3l-.3-.2-2.6.9.9-2.6-.2-.3a8.9 8.9 0 1 1 6.8 3.5Zm5-6.6c-.3-.1-1.6-.8-1.9-.9-.3-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.6.1-1-.4-1.8-.9-2.6-1.7-.7-.7-1.2-1.5-1.6-2.5-.1-.3 0-.4.1-.6l.5-.6c.1-.1.2-.3.3-.5.1-.2 0-.3 0-.5L9 6.7c-.1-.4-.3-.4-.5-.4h-.5c-.1 0-.4.1-.6.3-.3.3-1 1-1 2.4s1 2.8 1.2 3 1.9 3 4.6 4.2c.6.3 1.2.4 1.6.5.7.1 1.3.1 1.8 0 .6-.1 1.6-.7 1.8-1.3.2-.6.2-1.2.2-1.3-.1-.1-.2-.1-.5-.3Z"/></svg>
  <span class="text-sm font-semibold">CS</span>
</a>
@endsection
