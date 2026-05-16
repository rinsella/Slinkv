@extends('layouts.public')
@section('content')
<div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-16">
  <div class="text-center mb-12">
    <h1 class="text-4xl font-bold">Bagaimana SlinkV Bekerja</h1>
    <p class="mt-3 text-muted">Empat langkah sederhana untuk traffic yang lebih bersih.</p>
  </div>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
    @foreach ([
      ['1','Daftar Gratis','Buat akun dalam hitungan detik. Tidak perlu kartu kredit.'],
      ['2','Buat Short Link','Tempel URL panjang Anda dan dapatkan link pendek profesional.'],
      ['3','Sebarkan Link','Pakai di iklan, affiliate, sosial media, email — di mana saja.'],
      ['4','Pantau Analytics','Lihat data real-time: human, bot, sumber, negara, device.'],
    ] as [$n,$t,$d])
      <div class="rounded-2xl border border-line bg-white p-6 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-primary text-white flex items-center justify-center font-bold">{{ $n }}</div>
        <div class="mt-3 text-lg font-semibold">{{ $t }}</div>
        <p class="mt-2 text-sm text-muted">{{ $d }}</p>
      </div>
    @endforeach
  </div>
  <div class="text-center mt-10">
    <a href="{{ route('register') }}" class="px-6 py-3 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-700">Mulai Sekarang</a>
  </div>
</div>
@endsection
