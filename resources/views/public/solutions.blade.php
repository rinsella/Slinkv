@extends('layouts.public')
@section('content')
<div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-16">
  <div class="text-center mb-12">
    <h1 class="text-4xl font-bold">Solusi untuk Setiap Kebutuhan Digital</h1>
    <p class="mt-3 text-muted max-w-2xl mx-auto">Dari pixel ads hingga affiliate, SlinkV membantu menjaga kualitas traffic Anda.</p>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach ([
      ['01','Meta & Google Ads','Pixel Protection','Data masuk ke pixel hanya dari manusia asli sehingga algoritma iklan lebih fokus ke calon pembeli nyata.'],
      ['02','Affiliate Shopee/TikTok','Click Validation','Validasi klik affiliate agar Anda tahu sumber traffic mana yang benar-benar berkualitas.'],
      ['03','Digital Agency','Independent Audit Report','Laporan ke klien berisi total klik, bot diblokir, sumber traffic, negara, device, dan estimasi budget terselamatkan.'],
      ['04','Produk Digital & SaaS','Gateway Filtering','Bot diblokir sebelum menyentuh landing page, form, database, atau funnel utama.'],
      ['05','Media Buyer','Traffic Cleaning','Pisahkan traffic sampah dari source tertentu dan audit performa campaign.'],
      ['06','Publisher Ad Network','Traffic Pre-Validator','Filter traffic sebelum masuk ke halaman monetisasi.'],
      ['07','CPM Optimization','Quality Traffic Routing','Tingkatkan kualitas traffic agar interaksi lebih natural.'],
      ['08','Traffic Source Audit','Source ID Auditing','Catat source ID atau campaign ID yang mengirim traffic bot.'],
    ] as [$n,$t,$s,$d])
      <div class="rounded-2xl border border-line bg-white p-6 hover:shadow-card transition">
        <div class="text-xs font-bold text-primary">{{ $n }}</div>
        <div class="mt-2 text-lg font-semibold">{{ $t }}</div>
        <div class="mt-1 text-sm text-secondary font-medium">{{ $s }}</div>
        <p class="mt-3 text-sm text-muted">{{ $d }}</p>
      </div>
    @endforeach
  </div>
</div>
@endsection
