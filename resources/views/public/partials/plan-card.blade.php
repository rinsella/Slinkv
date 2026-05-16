@php
  $featured = ($plan->slug ?? '') === 'pro';
  $features = match($plan->slug ?? 'free') {
    'free' => ['5 link aktif','1.000 klik per link/bulan','Bot Protection Basic','Analytics 7 hari','Source traffic dasar','Device tracking dasar','Geo tracking dasar'],
    'starter' => ['20 link aktif','Unlimited klik','Bot Protection Advanced','Analytics 30 hari','Fallback URL','Geo filter 3 negara','QR Code'],
    'pro' => ['100 link aktif','Unlimited klik','Bot Protection Advanced','Analytics 90 hari','Geo filter unlimited','Fallback URL','Custom alias','Export CSV'],
    'business' => ['Unlimited link','Semua fitur Pro','Branded alias','Audit report PDF','Team member opsional','Dedicated support','Priority bot rules'],
    default => [],
  };
  $cta = match($plan->slug ?? 'free') {
    'free' => 'Mulai Gratis',
    'starter' => 'Mulai Starter',
    'pro' => 'Mulai Pro',
    'business' => 'Mulai Business',
    default => 'Pilih',
  };
  $period = match($plan->billing_period ?? 'free') {
    'monthly' => '/bulan',
    'yearly' => '/tahun',
    default => '',
  };
@endphp
<div class="rounded-2xl border {{ $featured ? 'border-primary shadow-card relative scale-[1.02]' : 'border-line' }} bg-white p-6 flex flex-col">
  @if ($featured)<div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-primary text-white text-[10px] font-bold tracking-wider">PALING POPULER</div>@endif
  @if (($plan->billing_period ?? '') === 'yearly')<div class="absolute top-4 right-4 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold">HEMAT 60%</div>@endif
  <div class="text-sm font-semibold text-primary uppercase tracking-wider">{{ $plan->name }}</div>
  <div class="mt-3 flex items-baseline gap-1">
    <span class="text-4xl font-extrabold">{{ $plan->formattedPrice() }}</span>
    <span class="text-muted text-sm">{{ $period }}</span>
  </div>
  <ul class="mt-6 space-y-2.5 text-sm text-ink flex-1">
    @foreach ($features as $f)
      <li class="flex items-start gap-2"><span class="mt-0.5 text-green-600">✓</span><span>{{ $f }}</span></li>
    @endforeach
  </ul>
  <a href="{{ auth()->check() ? route('dashboard.billing') : route('register') }}" class="mt-6 px-4 py-2.5 rounded-xl text-center text-sm font-semibold {{ $featured ? 'bg-primary text-white hover:bg-primary-700' : 'bg-ink text-white hover:opacity-90' }}">{{ $cta }}</a>
</div>
