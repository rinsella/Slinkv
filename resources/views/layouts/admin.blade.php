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
<div x-data="{ sidebar:false }" class="min-h-full">
  <div x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-black/40 z-40 lg:hidden" x-on:click="sidebar=false" style="display:none"></div>
  <aside class="fixed inset-y-0 left-0 z-50 w-[240px] bg-ink text-white flex flex-col transform lg:transform-none transition-transform" :class="sidebar?'translate-x-0':'-translate-x-full lg:translate-x-0'">
    <div class="px-5 h-16 flex items-center justify-between border-b border-white/10">
      <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold">slinkv <span class="text-xs text-primary">admin</span></a>
      <button class="lg:hidden p-2" x-on:click="sidebar=false" aria-label="Tutup">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
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
  <div class="lg:pl-[240px]">
    <header class="sticky top-0 z-30 h-16 bg-white border-b border-line flex items-center px-4 sm:px-6 gap-3">
      <button class="lg:hidden p-2 -ml-2" x-on:click="sidebar=true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <h1 class="text-lg font-semibold">@yield('title', 'Admin')</h1>
      <a href="{{ route('home') }}" class="ml-auto text-sm text-primary hover:underline">Lihat Site →</a>
    </header>
    @if (session('success'))<div class="mx-4 sm:mx-6 mt-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="mx-4 sm:mx-6 mt-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-200">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <main class="p-4 sm:p-6 lg:p-8">@yield('content')</main>
  </div>
</div>
@endsection
