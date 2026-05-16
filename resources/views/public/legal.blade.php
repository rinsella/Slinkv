@extends('layouts.public', ['title' => $title . ' - SlinkV'])
@section('content')
<div class="mx-auto max-w-3xl px-4 sm:px-6 py-16">
  <h1 class="text-4xl font-bold">{{ $title }}</h1>
  <div class="prose prose-slate mt-6 max-w-none">
    @switch($type)
      @case('terms')
        <p>Dengan menggunakan SlinkV, Anda menyetujui ketentuan berikut. Anda bertanggung jawab atas konten dan tujuan link yang Anda buat.</p>
        <p>Dilarang membuat shortlink yang mengarah ke konten ilegal, malware, phishing, atau pelanggaran hak cipta.</p>
        @break
      @case('privacy')
        <p>Kami menghormati privasi Anda. SlinkV menyimpan data analitik agregat dan tidak menyimpan IP mentah dalam bentuk plaintext untuk klik publik.</p>
        <p>Data analytics digunakan untuk memberikan layanan analytics kepada pemilik link.</p>
        @break
      @case('refund')
        <p>Refund dapat diajukan dalam 7 hari setelah pembayaran berhasil untuk paket berbayar pertama, jika layanan tidak dapat digunakan karena masalah teknis dari pihak kami.</p>
        @break
      @case('aup')
        <p>Acceptable Use Policy: dilarang menggunakan SlinkV untuk spam, scam, malware, materi ilegal, atau aktivitas yang melanggar hukum Indonesia.</p>
        @break
    @endswitch
    <p class="text-sm text-muted">Halaman ini akan diperbarui secara berkala. Silakan periksa kembali untuk pembaruan.</p>
  </div>
</div>
@endsection
