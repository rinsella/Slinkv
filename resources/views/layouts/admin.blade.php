@extends('layouts.base')
@php
  $user = auth()->user();
  $supportWa = \App\Models\Setting::get('support_whatsapp', '6281234567890');
  $items = [
    ['Dashboard', 'admin.dashboard'],
    ['Users', 'admin.users'],
    ['Short Links', 'admin.links'],
    ['Click Logs', 'admin.click-logs'],
    ['Bot Logs', 'admin.bot-logs'],
    ['Articles', 'admin.articles'],
    ['FAQs', 'admin.faqs'],
    ['Contact Messages', 'admin.contact-messages'],
    ['Settings', 'admin.settings'],
    ['Health Check', 'admin.health-check'],
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
    <nav class="flex-1 overflow-y-auto px-2 py-4 text-sm">
      @foreach ($items as [$name, $route])
        @php $active = request()->routeIs($route) || request()->routeIs($route.'.*'); @endphp
        <a href="{{ route($route) }}" class="block px-3 py-2.5 rounded-lg mb-0.5 {{ $active ? 'bg-primary text-white font-semibold' : 'text-white/80 hover:bg-white/10' }}">{{ $name }}</a>
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
