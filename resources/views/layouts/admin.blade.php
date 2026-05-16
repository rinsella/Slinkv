@extends('layouts.base')
@php
  $user = auth()->user();
  $supportWa = \App\Models\Setting::get('support_whatsapp', '6281234567890');
  $flaggedCount = \App\Models\ShortLink::where('is_flagged', true)->count();
  $pendingPaymentsCount = \App\Models\Payment::where('status', 'pending')->count();
  $unreadMessagesCount = \App\Models\ContactMessage::where('status', 'unread')->count();
  $openAbuseCount = \App\Models\AbuseReport::where('status', 'open')->count();
  $sections = [
    'OVERVIEW' => [
      ['Dashboard', 'admin.dashboard', null],
    ],
    'MANAGEMENT' => [
      ['Users', 'admin.users.index', null],
      ['Short Links', 'admin.links.index', $flaggedCount ?: null],
      ['Click Logs', 'admin.click-logs', null],
      ['Bot Logs', 'admin.bot-logs', null],
      ['Plans', 'admin.plans.index', null],
      ['Subscriptions', 'admin.subscriptions.index', null],
      ['Payments', 'admin.payments.index', $pendingPaymentsCount ?: null],
      ['Articles', 'admin.articles.index', null],
      ['FAQs', 'admin.faqs.index', null],
      ['Contact Messages', 'admin.contact-messages.index', $unreadMessagesCount ?: null],
    ],
    'SECURITY' => [
      ['Abuse Reports', 'admin.abuse-reports.index', $openAbuseCount ?: null],
      ['Blocked Domains', 'admin.blocked-domains.index', null],
      ['Blocked IPs', 'admin.blocked-ips.index', null],
      ['Bot Rules', 'admin.bot-rules.index', null],
      ['Audit Logs', 'admin.audit-logs', null],
    ],
    'SYSTEM' => [
      ['Settings', 'admin.settings', null],
      ['Health Check', 'admin.health-check', null],
    ],
  ];
@endphp
@section('body')
{{-- v0.4.8 — bulletproof admin shell: zero Alpine deps, inline-styled
     closed-by-default sidebar, ID-based vanilla JS toggle. Survives even
     if Tailwind/Alpine fail to load (e.g. LiteSpeed cache stripping). --}}
<style id="adm-shell-css">
  #adm-sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: 240px;
                 background: #0b1220; color: #fff; z-index: 50;
                 display: flex; flex-direction: column;
                 transform: translateX(-100%); transition: transform .22s ease;
                 will-change: transform; }
  #adm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45);
                 z-index: 40; display: none; }
  #adm-main   { min-height: 100vh; }
  #adm-shell[data-state="open"] #adm-sidebar { transform: translateX(0); }
  #adm-shell[data-state="open"] #adm-overlay { display: block; }
  @media (min-width: 1024px) {
    #adm-sidebar { transform: translateX(0) !important; }
    #adm-overlay { display: none !important; }
    #adm-main   { padding-left: 240px; }
    #adm-mobile-open, #adm-mobile-close { display: none !important; }
  }
  #adm-mobile-open, #adm-mobile-close {
    display: inline-flex; align-items: center; justify-content: center;
    width: 44px; height: 44px; background: transparent; border: 0;
    cursor: pointer; padding: 0;
  }
  #adm-mobile-open svg, #adm-mobile-close svg { pointer-events: none; }
</style>

<div id="adm-shell" data-state="closed" class="min-h-full">
  <div id="adm-overlay" data-adm-close></div>

  <aside id="adm-sidebar" aria-label="Admin navigation">
    <div class="px-5 h-16 flex items-center justify-between border-b border-white/10">
      <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold">slinkv <span class="text-xs text-primary">admin</span></a>
      <button type="button" id="adm-mobile-close" data-adm-close aria-label="Tutup menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
    <nav class="flex-1 overflow-y-auto px-2 py-3 text-sm">
      @foreach ($sections as $sectionTitle => $items)
        <div class="px-3 pt-3 pb-1 text-[10px] tracking-wider text-white/40 font-semibold">{{ $sectionTitle }}</div>
        @foreach ($items as [$name, $route, $badge])
          @php
            $exists = \Illuminate\Support\Facades\Route::has($route);
            $active = $exists && (request()->routeIs($route) || request()->routeIs(str_replace('.index', '', $route).'.*'));
          @endphp
          @if ($exists)
            <a href="{{ route($route) }}" class="flex items-center justify-between px-3 py-2 rounded-lg mb-0.5 {{ $active ? 'bg-primary text-white font-semibold' : 'text-white/80 hover:bg-white/10' }}">
              <span>{{ $name }}</span>
              @if ($badge)<span class="bg-red-500 text-white text-[10px] px-1.5 rounded-full">{{ $badge }}</span>@endif
            </a>
          @endif
        @endforeach
      @endforeach
    </nav>
    <div class="p-4 border-t border-white/10 text-xs">
      <div class="font-semibold">{{ $user?->name }}</div>
      <div class="text-white/60">Administrator</div>
      <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf
        <button class="w-full px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white text-sm">Logout</button>
      </form>
    </div>
  </aside>

  <div id="adm-main">
    <header class="sticky top-0 z-30 h-16 bg-white border-b border-line flex items-center px-4 sm:px-6 gap-3">
      <button type="button" id="adm-mobile-open" data-adm-open aria-label="Buka menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0b1220" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <h1 class="text-lg font-semibold">@yield('title', 'Admin')</h1>
      <a href="{{ route('home') }}" class="ml-auto text-sm text-primary hover:underline">Lihat Site →</a>
    </header>
    @if (session('success'))<div class="mx-4 sm:mx-6 mt-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="mx-4 sm:mx-6 mt-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-200">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <main class="p-4 sm:p-6 lg:p-8">@yield('content')</main>
  </div>
</div>

<script id="adm-shell-js">
(function () {
  var shell = document.getElementById('adm-shell');
  if (!shell) return;
  function setState(s) {
    shell.setAttribute('data-state', s);
    document.body.style.overflow = (s === 'open') ? 'hidden' : '';
  }
  // Force closed on every page load — prevents stale browser state.
  setState('closed');
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-adm-open]'))  { setState('open');   e.preventDefault(); }
    if (e.target.closest('[data-adm-close]')) { setState('closed'); e.preventDefault(); }
  }, false);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setState('closed');
  });
  // Auto-close on viewport resize to desktop
  window.addEventListener('resize', function () {
    if (window.innerWidth >= 1024) setState('closed');
  });
})();
</script>
@endsection
