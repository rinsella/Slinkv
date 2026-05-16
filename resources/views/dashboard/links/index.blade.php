@extends('layouts.dashboard')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-bold">Link Saya</h1>
    <p class="text-muted text-sm">{{ $activeCount }}{{ $plan->max_links ? ' / '.$plan->max_links : '' }} link aktif - paket {{ $plan->name }}.</p>
  </div>
  <a href="{{ route('dashboard.links.create') }}" class="px-4 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-700">+ Buat Shortlink</a>
</div>

<form method="GET" class="bg-white rounded-2xl border border-line p-3 mb-4 flex flex-col sm:flex-row gap-2">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari slug, judul, atau URL..." class="flex-1 rounded-xl border-line text-sm focus:ring-primary focus:border-primary">
  <select name="status" class="rounded-xl border-line text-sm focus:ring-primary focus:border-primary">
    <option value="">Semua status</option>
    <option value="active" @selected(request('status')==='active')>Aktif</option>
    <option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option>
    <option value="expired" @selected(request('status')==='expired')>Kedaluwarsa</option>
  </select>
  <select name="sort" class="rounded-xl border-line text-sm focus:ring-primary focus:border-primary">
    <option value="newest" @selected(request('sort','newest')==='newest')>Terbaru</option>
    <option value="clicks" @selected(request('sort')==='clicks')>Klik Terbanyak</option>
    <option value="bot" @selected(request('sort')==='bot')>Bot Rate Tertinggi</option>
  </select>
  <button class="px-4 py-2 rounded-xl bg-ink text-white text-sm">Filter</button>
</form>

<div class="bg-white rounded-2xl shadow-card border border-line overflow-hidden">
  @if ($links->isEmpty())
    <div class="p-12 text-center">
      <div class="text-5xl">🔗</div>
      <div class="mt-3 text-lg font-semibold">Belum ada shortlink</div>
      <div class="text-sm text-muted mt-1">Mulai buat shortlink pertama Anda dan pantau performanya secara real-time.</div>
      <a href="{{ route('dashboard.links.create') }}" class="mt-5 inline-block px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold">+ Buat Shortlink</a>
    </div>
  @else
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-surface text-muted text-xs">
          <tr>
            <th class="text-left p-3 font-medium">Link</th>
            <th class="text-left p-3 font-medium">Tujuan</th>
            <th class="text-right p-3 font-medium">Klik</th>
            <th class="text-right p-3 font-medium">Bot</th>
            <th class="text-center p-3 font-medium">Status</th>
            <th class="text-right p-3 font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line">
          @foreach ($links as $l)
          <tr>
            <td class="p-3">
              <div class="flex items-center gap-2">
                <a href="{{ url('/'.$l->slug) }}" target="_blank" rel="noopener" class="font-mono text-primary hover:underline">{{ url('/'.$l->slug) }}</a>
                <button type="button" data-copy="{{ url('/'.$l->slug) }}" class="js-copy text-xs px-2 py-0.5 rounded-md border border-line text-muted hover:bg-surface" title="Salin link">Copy</button>
              </div>
              <div class="text-xs text-muted">{{ $l->title ?: '-' }}</div>
            </td>
            <td class="p-3 max-w-xs truncate text-muted">{{ $l->destination_url }}</td>
            <td class="p-3 text-right">{{ number_format($l->total_clicks) }}</td>
            <td class="p-3 text-right text-red-600">{{ number_format($l->bot_clicks) }} <span class="text-xs text-muted">({{ $l->botRate() }}%)</span></td>
            <td class="p-3 text-center">
              @if ($l->isExpired())<span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-700 font-bold">EXP</span>
              @elseif ($l->is_active)<span class="px-2 py-0.5 rounded-full text-[10px] bg-green-100 text-green-700 font-bold">AKTIF</span>
              @else <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-bold">OFF</span>
              @endif
            </td>
            <td class="p-3 text-right space-x-2 whitespace-nowrap">
              <a href="{{ route('dashboard.links.analytics', $l) }}" class="text-primary text-xs font-semibold">Analytics</a>
              <a href="{{ route('dashboard.links.edit', $l) }}" class="text-ink text-xs font-semibold">Edit</a>
              <form method="POST" action="{{ route('dashboard.links.toggle', $l) }}" class="inline">@csrf @method('PATCH')
                <button type="submit" class="text-amber-600 text-xs font-semibold">{{ $l->is_active ? 'Off' : 'On' }}</button>
              </form>
              <form method="POST" action="{{ route('dashboard.links.destroy', $l) }}" class="inline" onsubmit="return confirm('Hapus link ini?')">@csrf @method('DELETE')
                <button type="submit" class="text-red-600 text-xs font-semibold">Hapus</button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="md:hidden divide-y divide-line">
      @foreach ($links as $l)
      <div class="p-4">
        <div class="flex items-start justify-between gap-2">
          <a href="{{ url('/'.$l->slug) }}" target="_blank" rel="noopener" class="font-mono text-primary text-sm break-all hover:underline">{{ url('/'.$l->slug) }}</a>
          <button type="button" data-copy="{{ url('/'.$l->slug) }}" class="js-copy shrink-0 text-xs px-2 py-1 rounded-md border border-line text-muted">Copy</button>
        </div>
        <div class="text-xs text-muted break-all mt-1">{{ $l->destination_url }}</div>
        <div class="flex gap-3 mt-2 text-xs text-muted">
          <span>Klik: <b class="text-ink">{{ $l->total_clicks }}</b></span>
          <span>Bot: <b class="text-red-600">{{ $l->bot_clicks }}</b></span>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
          <a href="{{ route('dashboard.links.analytics', $l) }}" class="px-3 py-1.5 rounded-lg bg-primary text-white text-xs">Analytics</a>
          <a href="{{ route('dashboard.links.edit', $l) }}" class="px-3 py-1.5 rounded-lg border border-line text-xs">Edit</a>
          <form method="POST" action="{{ route('dashboard.links.toggle', $l) }}" class="inline">@csrf @method('PATCH')
            <button type="submit" class="px-3 py-1.5 rounded-lg border border-line text-xs text-amber-600">{{ $l->is_active ? 'Off' : 'On' }}</button>
          </form>
          <form method="POST" action="{{ route('dashboard.links.destroy', $l) }}" class="inline" onsubmit="return confirm('Hapus link ini?')">@csrf @method('DELETE')
            <button type="submit" class="px-3 py-1.5 rounded-lg border border-line text-xs text-red-600">Hapus</button>
          </form>
        </div>
      </div>
      @endforeach
    </div>
    <div class="p-4 border-t border-line">{{ $links->links() }}</div>
  @endif
</div>
<script>
(function(){
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.js-copy');
    if (!btn) return;
    e.preventDefault();
    var text = btn.getAttribute('data-copy') || '';
    var done = function(){
      var old = btn.textContent;
      btn.textContent = 'Tersalin!';
      btn.classList.add('text-green-600');
      setTimeout(function(){ btn.textContent = old; btn.classList.remove('text-green-600'); }, 1500);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done).catch(fallback);
    } else { fallback(); }
    function fallback(){
      var ta = document.createElement('textarea');
      ta.value = text; ta.style.position='fixed'; ta.style.opacity='0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); done(); } catch(_) {}
      document.body.removeChild(ta);
    }
  });
})();
</script>
@endsection
