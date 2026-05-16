@extends('layouts.public')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
  <h1 class="text-3xl font-bold">Laporkan Penyalahgunaan</h1>
  <p class="mt-2 text-muted">Bantu kami menjaga SlinkV tetap aman. Laporkan link yang Anda anggap menyebarkan phishing, malware, penipuan, konten ilegal, atau pelanggaran lain.</p>

  @if (session('success'))
    <div class="mt-6 px-4 py-3 rounded-xl bg-green-50 text-green-700 border border-green-200 text-sm">{{ session('success') }}</div>
  @endif

  @if (!$enabled)
    <div class="mt-8 bg-white border border-line rounded-2xl p-6 text-center">
      <div class="text-4xl">🚧</div>
      <h2 class="mt-3 font-semibold">Formulir laporan sedang dinonaktifkan sementara</h2>
      <p class="mt-2 text-sm text-muted">Silakan hubungi tim support kami untuk melaporkan penyalahgunaan.</p>
      <a href="{{ route('contact') }}" class="mt-5 inline-block px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold">Hubungi Support</a>
    </div>
  @else
    <form method="POST" action="{{ route('abuse.store') }}" class="mt-6 bg-white border border-line rounded-2xl p-6 space-y-4">
      @csrf
      @if ($errors->any())
        <div class="px-3 py-2 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">
          <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      @endif

      <div>
        <label class="block text-sm font-medium mb-1">Short URL yang dilaporkan <span class="text-red-600">*</span></label>
        <input type="text" name="short_url" value="{{ old('short_url') }}" required maxlength="500"
          placeholder="https://contoh.com/abc123"
          class="w-full rounded-xl border-line text-sm focus:ring-primary focus:border-primary">
        <p class="mt-1 text-xs text-muted">Tempel URL pendek lengkap yang ingin dilaporkan.</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Email Anda (opsional)</label>
        <input type="email" name="reporter_email" value="{{ old('reporter_email') }}" maxlength="255"
          placeholder="anda@email.com"
          class="w-full rounded-xl border-line text-sm focus:ring-primary focus:border-primary">
        <p class="mt-1 text-xs text-muted">Hanya digunakan jika kami perlu menghubungi Anda.</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Alasan laporan <span class="text-red-600">*</span></label>
        <textarea name="reason" rows="6" required minlength="10" maxlength="5000"
          placeholder="Jelaskan kenapa link ini menyalahgunakan layanan..."
          class="w-full rounded-xl border-line text-sm focus:ring-primary focus:border-primary">{{ old('reason') }}</textarea>
      </div>

      <div class="flex items-center justify-between">
        <p class="text-xs text-muted">Maksimal 5 laporan per menit dari IP yang sama.</p>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-700 text-white text-sm font-semibold">Kirim Laporan</button>
      </div>
    </form>
  @endif
</div>
@endsection
