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
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          primary: { DEFAULT: '#2563EB', 600: '#2563EB', 700: '#1D4ED8' },
          secondary: '#4F46E5',
          ink: '#0F172A',
          muted: '#64748B',
          line: '#E2E8F0',
          surface: '#F8FAFC',
        },
        borderRadius: { xl: '14px', '2xl': '18px' },
        boxShadow: { card: '0 1px 2px rgba(15,23,42,.04), 0 4px 16px rgba(15,23,42,.04)' }
      }
    }
  }
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
  body{font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica Neue,Arial,sans-serif}
  .brand-slink,.brand-v{display:inline-block;vertical-align:baseline;font-weight:800;letter-spacing:-.025em;line-height:1;white-space:nowrap}
  .brand-slink{color:#1E3A8A}
  .brand-v{background:linear-gradient(90deg,#2563EB,#7C3AED);-webkit-background-clip:text;background-clip:text;color:transparent;margin-left:-.05em}
</style>
@stack('head')
</head>
<body class="h-full bg-surface text-ink antialiased">
@yield('body')
@stack('scripts')
</body>
</html>
