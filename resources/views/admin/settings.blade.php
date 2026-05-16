@extends('layouts.admin')
@section('title','Settings')
@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded-2xl border border-line p-6 max-w-3xl space-y-4">
  @csrf @method('PUT')
  @php
    $fields = [
      'site_name' => 'Nama Situs',
      'site_title' => 'Title Tag',
      'meta_description' => 'Meta Description',
      'site_url' => 'URL Situs',
      'support_email' => 'Email Support',
      'support_whatsapp' => 'WhatsApp Support (628…)',
      'registration_enabled' => 'Registrasi Dibuka (1/0)',
      'free_plan_enabled' => 'Paket Free Aktif (1/0)',
      'default_plan' => 'Default Plan',
      'maintenance_mode' => 'Mode Maintenance (1/0)',
      'payment_gateway_mode' => 'Mode Gateway (manual)',
      'analytics_retention_default' => 'Retensi Analytics Default (hari)',
    ];
  @endphp
  @foreach ($fields as $key => $label)
    <div>
      <label class="block text-sm font-medium mb-1">{{ $label }}</label>
      @if ($key === 'meta_description')
        <textarea name="{{ $key }}" rows="3" class="w-full rounded-xl border-line">{{ $settings[$key]?->value ?? '' }}</textarea>
      @else
        <input name="{{ $key }}" value="{{ $settings[$key]?->value ?? '' }}" class="w-full rounded-xl border-line">
      @endif
    </div>
  @endforeach
  <button class="px-5 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-primary-700">Simpan Pengaturan</button>
</form>
@endsection
