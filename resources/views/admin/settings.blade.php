@extends('layouts.admin')
@section('title','Settings')
@section('content')
@php $g = fn($k, $d='') => $settings[$k]->value ?? $d; @endphp
<style>
  .stab-panel { display: none; }
  .stab-panel.is-active { display: block; }
  .stab-btn { border-bottom: 2px solid transparent; color: #64748B; }
  .stab-btn.is-active { border-color: #2563EB; color: #2563EB; }
</style>

<div id="settings-tabs">
  <div class="flex flex-wrap gap-1 border-b border-line mb-5 text-sm">
    @foreach (['general'=>'General','seo'=>'SEO','support'=>'Support','beta'=>'Beta Mode','billing'=>'Billing','security'=>'Security','maintenance'=>'Maintenance'] as $k => $label)
      <button type="button" data-stab="{{ $k }}" class="stab-btn px-4 py-2 font-semibold {{ $loop->first ? 'is-active' : '' }}">{{ $label }}</button>
    @endforeach
  </div>

  <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded-2xl border border-line p-6 max-w-3xl space-y-5">
    @csrf @method('PUT')

    <div data-stab-panel="general" class="stab-panel is-active space-y-4">
      <div><label class="block text-sm font-medium mb-1">Site Name</label><input name="site_name" value="{{ $g('site_name','SlinkV') }}" class="w-full rounded-xl border-line"></div>
      <div><label class="block text-sm font-medium mb-1">Site URL</label><input name="site_url" value="{{ $g('site_url') }}" class="w-full rounded-xl border-line"></div>
      <div><label class="block text-sm font-medium mb-1">Default Plan Slug</label><input name="default_plan" value="{{ $g('default_plan','free') }}" class="w-full rounded-xl border-line"></div>
      <label class="flex items-center gap-2"><input type="hidden" name="__toggle_registration_enabled" value="1"><input type="hidden" name="registration_enabled" value="0"><input type="checkbox" name="registration_enabled" value="1" @checked($g('registration_enabled','1')==='1')> Registrasi terbuka</label>
      <label class="flex items-center gap-2"><input type="hidden" name="__toggle_free_plan_enabled" value="1"><input type="hidden" name="free_plan_enabled" value="0"><input type="checkbox" name="free_plan_enabled" value="1" @checked($g('free_plan_enabled','1')==='1')> Free plan aktif</label>
    </div>

    <div data-stab-panel="seo" class="stab-panel space-y-4">
      <div><label class="block text-sm font-medium mb-1">Site Title</label><input name="site_title" value="{{ $g('site_title') }}" class="w-full rounded-xl border-line"></div>
      <div><label class="block text-sm font-medium mb-1">Meta Description</label><textarea name="meta_description" rows="3" class="w-full rounded-xl border-line">{{ $g('meta_description') }}</textarea></div>
      <div><label class="block text-sm font-medium mb-1">OG Image URL</label><input name="og_image" value="{{ $g('og_image') }}" class="w-full rounded-xl border-line"></div>
      <div><label class="block text-sm font-medium mb-1">Favicon URL</label><input name="favicon" value="{{ $g('favicon') }}" class="w-full rounded-xl border-line"></div>
    </div>

    <div data-stab-panel="support" class="stab-panel space-y-4">
      <div><label class="block text-sm font-medium mb-1">Support Email</label><input type="email" name="support_email" value="{{ $g('support_email') }}" class="w-full rounded-xl border-line"></div>
      <div><label class="block text-sm font-medium mb-1">Support WhatsApp</label><input name="support_whatsapp" value="{{ $g('support_whatsapp') }}" placeholder="+62..." class="w-full rounded-xl border-line"></div>
    </div>

    <div data-stab-panel="beta" class="stab-panel space-y-4">
      <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900">Selama beta aktif, semua user mendapatkan akses fitur Business gratis. Tabel paket, subscription, dan payment tetap utuh.</div>
      <label class="flex items-center gap-2"><input type="hidden" name="__toggle_beta_mode_enabled" value="1"><input type="hidden" name="beta_mode_enabled" value="0"><input type="checkbox" name="beta_mode_enabled" value="1" @checked($g('beta_mode_enabled','1')==='1')> Beta mode aktif</label>
      <label class="flex items-center gap-2"><input type="hidden" name="__toggle_beta_free_all_features" value="1"><input type="hidden" name="beta_free_all_features" value="0"><input type="checkbox" name="beta_free_all_features" value="1" @checked($g('beta_free_all_features','1')==='1')> Semua fitur gratis (bypass limit)</label>
      <label class="flex items-center gap-2"><input type="hidden" name="__toggle_beta_banner_enabled" value="1"><input type="hidden" name="beta_banner_enabled" value="0"><input type="checkbox" name="beta_banner_enabled" value="1" @checked($g('beta_banner_enabled','1')==='1')> Tampilkan banner beta</label>
      <div><label class="block text-sm font-medium mb-1">Beta Ends At</label><input type="datetime-local" name="beta_ends_at" value="{{ $g('beta_ends_at') }}" class="w-full rounded-xl border-line"></div>
      <div><label class="block text-sm font-medium mb-1">Teks Pengumuman</label><textarea name="beta_announcement_text" rows="2" class="w-full rounded-xl border-line">{{ $g('beta_announcement_text') }}</textarea></div>
    </div>

    <div data-stab-panel="billing" class="stab-panel space-y-4">
      <div><label class="block text-sm font-medium mb-1">Gateway Mode</label><select name="payment_gateway_mode" class="w-full rounded-xl border-line">@foreach (['manual','midtrans','xendit'] as $m)<option value="{{ $m }}" @selected($g('payment_gateway_mode','manual')===$m)>{{ ucfirst($m) }}</option>@endforeach</select></div>
      <div><label class="block text-sm font-medium mb-1">Invoice Expire (jam)</label><input type="number" min="1" max="720" name="invoice_expiration_hours" value="{{ $g('invoice_expiration_hours','24') }}" class="w-full rounded-xl border-line"></div>
      <div><label class="block text-sm font-medium mb-1">Instruksi Pembayaran Manual</label><textarea name="manual_payment_instruction" rows="6" class="w-full rounded-xl border-line">{{ $g('manual_payment_instruction') }}</textarea></div>
    </div>

    <div data-stab-panel="security" class="stab-panel space-y-4">
      <label class="flex items-center gap-2"><input type="hidden" name="__toggle_block_private_urls" value="1"><input type="hidden" name="block_private_urls" value="0"><input type="checkbox" name="block_private_urls" value="1" @checked($g('block_private_urls','1')==='1')> Blokir URL ke jaringan private (SSRF protection)</label>
      <label class="flex items-center gap-2"><input type="hidden" name="__toggle_enable_abuse_report" value="1"><input type="hidden" name="enable_abuse_report" value="0"><input type="checkbox" name="enable_abuse_report" value="1" @checked($g('enable_abuse_report','1')==='1')> Aktifkan form abuse report</label>
      <div><label class="block text-sm font-medium mb-1">Default Bot Threshold (0-100)</label><input type="number" min="0" max="100" name="default_bot_threshold" value="{{ $g('default_bot_threshold','40') }}" class="w-full rounded-xl border-line"></div>
      <div><label class="block text-sm font-medium mb-1">Redirect Rate Limit (per menit per IP)</label><input type="number" min="10" max="10000" name="redirect_rate_limit" value="{{ $g('redirect_rate_limit','120') }}" class="w-full rounded-xl border-line"></div>
    </div>

    <div data-stab-panel="maintenance" class="stab-panel space-y-4">
      <label class="flex items-center gap-2"><input type="hidden" name="__toggle_maintenance_mode" value="1"><input type="hidden" name="maintenance_mode" value="0"><input type="checkbox" name="maintenance_mode" value="1" @checked($g('maintenance_mode','0')==='1')> Aktifkan maintenance mode</label>
      <div><label class="block text-sm font-medium mb-1">Pesan Maintenance</label><textarea name="maintenance_message" rows="3" class="w-full rounded-xl border-line">{{ $g('maintenance_message') }}</textarea></div>
    </div>

    <div class="pt-3 border-t border-line"><button type="submit" class="px-6 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">Simpan Pengaturan</button></div>
  </form>
</div>

<script>
(function(){
  var root = document.getElementById('settings-tabs');
  if (!root) return;
  var btns = root.querySelectorAll('[data-stab]');
  var panels = root.querySelectorAll('[data-stab-panel]');
  function activate(key) {
    btns.forEach(function(b){ b.classList.toggle('is-active', b.dataset.stab === key); });
    panels.forEach(function(p){ p.classList.toggle('is-active', p.dataset.stabPanel === key); });
    try { history.replaceState(null, '', '#' + key); } catch(e){}
  }
  btns.forEach(function(b){ b.addEventListener('click', function(){ activate(b.dataset.stab); }); });
  var initial = (location.hash || '').replace('#','');
  if (initial) activate(initial);
})();
</script>
@endsection
