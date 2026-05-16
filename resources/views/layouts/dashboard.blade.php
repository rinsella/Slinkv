@extends('layouts.base')

@php
  $user = auth()->user();
  $plan = $user?->effectivePlan();
  $supportWa = \App\Models\Setting::get('support_whatsapp', '6281234567890');
@endphp

@section('body')
<div x-data="{ sidebar: false }" class="min-h-full">
  <!-- Mobile overlay -->
  <div x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-black/40 z-40 lg:hidden" x-on:click="sidebar=false" style="display:none"></div>

  <!-- Sidebar -->
  <aside class="fixed inset-y-0 left-0 z-50 w-[240px] bg-white border-r border-line flex flex-col transform lg:transform-none transition-transform"
         :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    <div class="px-5 h-16 flex items-center justify-between border-b border-line">
      <a href="{{ route('dashboard.index') }}" class="text-2xl">
        <span class="brand-slink">slink</span><span class="brand-v">v</span>
      </a>
      <button class="lg:hidden p-2" x-on:click="sidebar=false" aria-label="Tutup">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4 text-sm">
      @php
        $sections = [
          'UTAMA' => [
            ['Dashboard', 'dashboard.index', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Link Saya', 'dashboard.links.index', 'M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1'],
          ],
          'ANALYTICS' => [
            ['Statistik', 'dashboard.statistics', 'M3 3v18h18M7 14l4-4 4 4 5-7'],
            ['Lokasi & Device', 'dashboard.location-device', 'M12 22s7-7.58 7-13a7 7 0 1 0-14 0c0 5.42 7 13 7 13zM12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4z'],
            ['Sumber Traffic', 'dashboard.sources', 'M4 12h16M12 4v16M6 6l12 12'],
          ],
          'LAINNYA' => [
            ['Referral', 'dashboard.referral', 'M16 11a4 4 0 1 0-8 0M2 21a8 8 0 0 1 16 0M19 9l2 2-2 2'],
            ['Pengaturan', 'dashboard.settings', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19 12a7 7 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7 7 0 0 0-1.7-1L14 3h-4l-.8 2.5a7 7 0 0 0-1.7 1l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1c.5.4 1.1.7 1.7 1L10 21h4l.8-2.5c.6-.3 1.2-.6 1.7-1l2.4 1 2-3.4-2-1.6c.1-.3.1-.7.1-1z'],
          ],
        ];
      @endphp
      @foreach ($sections as $label => $items)
        <div class="px-2 mt-4 mb-2 text-[10px] font-semibold tracking-wider text-muted">{{ $label }}</div>
        @foreach ($items as [$name, $route, $path])
          @php $active = request()->routeIs($route) || request()->routeIs($route.'.*'); @endphp
          <a href="{{ route($route) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1 {{ $active ? 'bg-primary/10 text-primary font-semibold' : 'text-ink hover:bg-surface' }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
            <span>{{ $name }}</span>
          </a>
        @endforeach
      @endforeach
    </nav>
    <div class="p-3 border-t border-line space-y-3">
      <a href="https://wa.me/{{ $supportWa }}" target="_blank" rel="noopener" class="flex items-center gap-2 px-3 py-2 rounded-xl border border-line text-sm font-medium hover:bg-surface">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="#25D366"><path d="M20.5 3.5A11 11 0 0 0 3.6 17.7L2 22l4.4-1.5A11 11 0 1 0 20.5 3.5Z"/></svg>
        Customer Service
      </a>
      <div class="flex items-center gap-3 px-2">
        <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold">{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold truncate">{{ $user?->name }}</div>
          <div class="text-[11px] uppercase tracking-wider text-primary">Beta · Gratis</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="p-2 text-muted hover:text-danger" title="Logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="lg:pl-[240px]">
    <header class="sticky top-0 z-30 h-16 bg-white border-b border-line flex items-center px-4 sm:px-6 gap-3">
      <button class="lg:hidden p-2 -ml-2" x-on:click="sidebar=true" aria-label="Menu">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <form method="GET" action="{{ route('dashboard.links.index') }}" class="hidden sm:flex flex-1 max-w-md">
        <div class="relative w-full">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          </span>
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari link..." class="pl-9 pr-3 py-2 w-full text-sm rounded-xl border-line bg-surface focus:bg-white focus:ring-primary focus:border-primary">
        </div>
      </form>
      <div class="ml-auto flex items-center gap-3">
        <a href="https://wa.me/{{ $supportWa }}" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-line text-sm font-medium hover:bg-surface">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="#25D366"><path d="M20.5 3.5A11 11 0 0 0 3.6 17.7L2 22l4.4-1.5A11 11 0 1 0 20.5 3.5Z"/></svg>
          Hubungi Kami
        </a>
        <button class="p-2 rounded-xl hover:bg-surface text-muted" aria-label="Notifications">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 16v-5a6 6 0 1 0-12 0v5l-2 2h16zM10 21a2 2 0 0 0 4 0"/></svg>
        </button>
        <span class="hidden md:inline px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider bg-primary/10 text-primary">BETA · GRATIS</span>
        <div class="hidden sm:flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</div>
          <span class="text-sm font-medium">{{ $user?->name }}</span>
        </div>
      </div>
    </header>

    @if (session('success'))
      <div class="mx-4 sm:mx-6 mt-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>
    @endif
    @if (session('error') || $errors->any())
      <div class="mx-4 sm:mx-6 mt-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-200">
        {{ session('error') }}
        @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
      </div>
    @endif

    <main class="p-4 sm:p-6 lg:p-8">@yield('content')</main>
  </div>
</div>
@endsection
