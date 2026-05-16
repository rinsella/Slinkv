@php
    $siteName = \App\Models\Setting::get('site_name', 'SlinkV');
    $siteTitle = \App\Models\Setting::get('site_title', 'SlinkV - URL Shortener dengan Bot Protection & Analytics Real-time');
    $metaDesc = \App\Models\Setting::get('meta_description', 'SlinkV adalah URL shortener profesional dengan analytics real-time, bot protection, dan device tracking.');
    $supportWa = \App\Models\Setting::get('support_whatsapp', '6281234567890');
@endphp
<!doctype html>
<html lang="id" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $title ?? $siteTitle }}</title>
<meta name="description" content="{{ $description ?? $metaDesc }}">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:title" content="{{ $title ?? $siteTitle }}">
<meta property="og:description" content="{{ $description ?? $metaDesc }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ url('/og-image.svg') }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="{{ url('/favicon.svg') }}" type="image/svg+xml">
<link rel="alternate icon" href="{{ url('/favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ url('/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ url('/site.webmanifest') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
{{-- Fallback: load Alpine.js from CDN if the Vite-built bundle failed to expose it.
     Some shared hosts (LiteSpeed cache, mod_pagespeed, aggressive CDNs) corrupt or
     drop the bundled JS — without Alpine the mobile sidebar gets stuck open. --}}
<script>
window.addEventListener('DOMContentLoaded', function () {
    if (typeof window.Alpine === 'undefined') {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js';
        s.defer = true;
        document.head.appendChild(s);
    }
});
</script>
{{-- slv-vanilla-handler-v2 — bulletproof vanilla-JS toggles (sidebar + mob-nav).
     Works whether Alpine loads or not. Uses event delegation + data-* hooks. --}}
<style>
@media (max-width: 1023.98px) {
  body:not(.sidebar-open) .slv-sidebar { transform: translateX(-100%) !important; }
  body:not(.sidebar-open) .slv-sidebar-overlay { display: none !important; }
  body.sidebar-open .slv-sidebar { transform: translateX(0) !important; }
  body.sidebar-open .slv-sidebar-overlay { display: block !important; }
  body.sidebar-open { overflow: hidden; }
}
</style>
<script>
(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }
  ready(function () {
    document.addEventListener('click', function (e) {
      var open    = e.target.closest('[data-sidebar-open]');
      var close   = e.target.closest('[data-sidebar-close]');
      var over    = e.target.closest('[data-sidebar-overlay]');
      var mobBtn  = e.target.closest('[data-mobnav-toggle]');
      if (open)            { document.body.classList.add('sidebar-open');    e.preventDefault(); }
      if (close || over)   { document.body.classList.remove('sidebar-open'); e.preventDefault(); }
      if (mobBtn) {
        var panel = document.querySelector('[data-mobnav]') || document.getElementById('mob-nav');
        if (panel) { panel.classList.toggle('hidden'); e.preventDefault(); }
      }
    }, false);
    // ESC closes sidebar + mob-nav
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        document.body.classList.remove('sidebar-open');
        var panel = document.querySelector('[data-mobnav]') || document.getElementById('mob-nav');
        if (panel && !panel.classList.contains('hidden')) { panel.classList.add('hidden'); }
      }
    });
  });
})();
</script>
@stack('head')
</head>
<body class="h-full bg-surface text-ink antialiased">
@yield('body')
@stack('scripts')
</body>
</html>
