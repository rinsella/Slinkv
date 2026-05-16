@extends('layouts.public')

@section('content')
<section class="relative overflow-hidden">
  <div class="absolute inset-0 -z-10 bg-gradient-to-br from-white via-blue-50/60 to-indigo-50/60"></div>
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-16 pb-12 lg:pt-24 lg:pb-20 grid lg:grid-cols-2 gap-12 items-center">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold">
        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span> Real-time Bot Protection
      </div>
      <h1 class="mt-4 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight text-ink">
        Link Lebih Pendek.<br><span class="bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">Traffic Lebih Bersih.</span>
      </h1>
      <p class="mt-5 text-lg text-muted max-w-xl">URL shortener profesional dengan analytics real-time dan bot protection canggih. Lindungi iklan, affiliate, dan konten digital dari traffic palsu yang menghabiskan budget.</p>

      <form method="POST" action="{{ route('quick-shorten') }}" class="mt-8 bg-white p-2 rounded-2xl shadow-card border border-line flex flex-col sm:flex-row gap-2">
        @csrf
        <input type="url" name="destination_url" required placeholder="https://contoh.com/link-sangat-panjang"
          class="flex-1 px-4 py-3 text-sm rounded-xl border-0 focus:ring-0 placeholder:text-muted">
        <button class="px-5 py-3 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-700">Dapatkan Link Gratis</button>
      </form>
      @error('destination_url')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
      <p class="mt-3 text-xs text-muted">100% gratis selama tahap beta — tidak perlu kartu kredit.</p>

      <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('register') }}" class="px-5 py-3 rounded-xl bg-ink text-white text-sm font-semibold hover:opacity-90">Daftar Gratis</a>
        <a href="{{ route('how-it-works') }}" class="px-5 py-3 rounded-xl border border-line text-sm font-semibold hover:bg-white">Cara Kerja</a>
      </div>

      <dl class="mt-10 grid grid-cols-2 sm:grid-cols-4 gap-5">
        @foreach ([['99.2%','Akurasi Bot Detection'], ['<50ms','Redirect Engine'], ['Real-time','Analytics'], ['24/7','Traffic Monitoring']] as [$v,$l])
        <div>
          <dt class="text-2xl font-bold text-ink">{{ $v }}</dt>
          <dd class="text-xs text-muted mt-1">{{ $l }}</dd>
        </div>
        @endforeach
      </dl>
    </div>

    <div class="relative">
      <div class="rounded-2xl bg-white border border-line shadow-card p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="text-xs text-muted">Traffic 24 Jam</div>
            <div class="text-2xl font-bold">12.480 klik</div>
          </div>
          <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded-full">▲ 12.4%</span>
        </div>
        <div class="h-32"><canvas id="heroChart" height="120"></canvas></div>
        <div class="grid grid-cols-3 gap-3 mt-4 text-center">
          <div class="rounded-xl bg-blue-50 p-3"><div class="text-xs text-muted">Human</div><div class="font-bold text-primary">9.842</div></div>
          <div class="rounded-xl bg-red-50 p-3"><div class="text-xs text-muted">Bot</div><div class="font-bold text-red-600">2.638</div></div>
          <div class="rounded-xl bg-indigo-50 p-3"><div class="text-xs text-muted">Bot Rate</div><div class="font-bold text-secondary">21.1%</div></div>
        </div>
      </div>
      <div class="mt-4 grid grid-cols-2 gap-4">
        <div class="rounded-2xl bg-white border border-line p-4 shadow-card">
          <div class="text-xs text-muted mb-2">Top Negara</div>
          @foreach ([['Indonesia',64],['Malaysia',12],['Singapura',8]] as [$n,$p])
          <div class="flex items-center justify-between text-sm py-1"><span>{{ $n }}</span><span class="text-muted">{{ $p }}%</span></div>
          @endforeach
        </div>
        <div class="rounded-2xl bg-white border border-line p-4 shadow-card">
          <div class="text-xs text-muted mb-2">Sumber Traffic</div>
          @foreach ([['Facebook',38],['TikTok',24],['Direct',18]] as [$n,$p])
          <div class="flex items-center justify-between text-sm py-1"><span>{{ $n }}</span><span class="text-muted">{{ $p }}%</span></div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-16 bg-white border-y border-line">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
    <h2 class="text-3xl sm:text-4xl font-bold">Dipakai untuk berbagai use case</h2>
    <p class="mt-2 text-muted max-w-2xl mx-auto">Cocok untuk advertiser, affiliate, agency, publisher, hingga tim media buyer.</p>
    <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
      @foreach ([
        ['Meta & Google Ads','Pixel Protection — pixel hanya menerima data dari manusia asli.'],
        ['Affiliate Shopee/TikTok','Click Validation — tahu sumber affiliate yang berkualitas.'],
        ['Digital Agency','Independent Audit Report untuk klien.'],
        ['Media Buyer','Pisahkan traffic sampah dari source tertentu.'],
      ] as [$t,$d])
      <div class="text-left rounded-2xl border border-line p-5 hover:shadow-card transition">
        <div class="text-xs font-bold text-primary uppercase tracking-wider">Use Case</div>
        <div class="mt-2 text-lg font-semibold">{{ $t }}</div>
        <p class="mt-2 text-sm text-muted">{{ $d }}</p>
      </div>
      @endforeach
    </div>
    <div class="mt-8"><a href="{{ route('solutions') }}" class="text-primary text-sm font-semibold hover:underline">Lihat semua solusi →</a></div>
  </div>
</section>

<section class="py-16">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto text-center rounded-2xl bg-gradient-to-br from-primary to-secondary text-white p-10 shadow-card">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-xs font-bold uppercase tracking-wider">Beta</div>
      <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold">Semua Fitur. 100% Gratis.</h2>
      <p class="mt-3 text-white/85 max-w-xl mx-auto">Selama masa beta, seluruh fitur SlinkV — termasuk link unlimited, analytics lengkap, bot protection canggih, custom alias, dan QR code — tersedia gratis untuk semua pengguna.</p>
      <div class="mt-6 flex flex-wrap gap-3 justify-center">
        <a href="{{ route('register') }}" class="px-6 py-3 rounded-xl bg-white text-primary text-sm font-bold hover:opacity-90">Daftar Sekarang</a>
        <a href="{{ route('how-it-works') }}" class="px-6 py-3 rounded-xl bg-white/10 border border-white/20 text-sm font-semibold hover:bg-white/15">Pelajari Selengkapnya</a>
      </div>
    </div>
  </div>
</section>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('heroChart');
    if (!ctx) return;
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['00','03','06','09','12','15','18','21'],
        datasets: [
          { label:'Human', data:[280,420,510,720,820,940,860,720], borderColor:'#2563EB', backgroundColor:'rgba(37,99,235,.1)', tension:.4, fill:true, borderWidth:2, pointRadius:0 },
          { label:'Bot', data:[40,60,80,180,160,200,140,90], borderColor:'#EF4444', backgroundColor:'rgba(239,68,68,.08)', tension:.4, fill:true, borderWidth:2, pointRadius:0 }
        ]
      },
      options: { plugins:{legend:{display:false}}, scales:{y:{display:false},x:{grid:{display:false},ticks:{color:'#94a3b8',font:{size:10}}}}, maintainAspectRatio:false }
    });
  });
</script>
@endpush
@endsection
