@extends('layouts.admin')
@section('title','Abuse Report #'.$report->id)
@section('content')
<a href="{{ route('admin.abuse-reports.index') }}" class="text-sm text-primary">← Daftar</a>
<div class="grid lg:grid-cols-3 gap-5 mt-3">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-line p-6">
    <div class="flex items-start justify-between">
      <h2 class="text-xl font-bold">Laporan #{{ $report->id }}</h2>
      <span class="px-2 py-0.5 rounded-full text-xs {{ $report->status==='open'?'bg-red-50 text-red-700':'bg-slate-100' }}">{{ $report->status }}</span>
    </div>
    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
      <div><dt class="text-muted text-xs">Pelapor</dt><dd>{{ $report->reporter_email ?: 'anonim' }}</dd></div>
      <div><dt class="text-muted text-xs">Dilaporkan</dt><dd>{{ $report->created_at?->format('d M Y H:i') }}</dd></div>
      <div class="col-span-2"><dt class="text-muted text-xs">Link Terlapor</dt><dd>
        @if ($report->shortLink)
          <a href="{{ route('admin.links.show', $report->shortLink) }}" class="text-primary font-mono">{{ $report->shortLink->slug }}</a>
          →
          <span class="text-muted">{{ \Illuminate\Support\Str::limit($report->shortLink->destination_url, 80) }}</span>
        @else
          <span class="text-muted">{{ $report->short_url ?: '-' }}</span>
        @endif
      </dd></div>
      <div class="col-span-2"><dt class="text-muted text-xs mb-1">Alasan</dt><dd class="whitespace-pre-wrap">{{ $report->reason }}</dd></div>
      @if ($report->admin_action)<div class="col-span-2"><dt class="text-muted text-xs mb-1">Tindakan Admin</dt><dd class="whitespace-pre-wrap p-3 bg-amber-50 rounded-xl">{{ $report->admin_action }}</dd></div>@endif
    </dl>
  </div>
  <div class="space-y-3">
    <form method="POST" action="{{ route('admin.abuse-reports.review', $report) }}" class="bg-white rounded-2xl border border-line p-5 space-y-2">@csrf @method('PATCH')
      <label class="text-sm font-medium">Catatan tindakan</label>
      <textarea name="admin_action" rows="4" class="w-full rounded-xl border-line text-sm">{{ $report->admin_action }}</textarea>
      <button class="w-full px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold">Mark Reviewed</button>
    </form>
    @if ($report->shortLink)
      <form method="POST" action="{{ route('admin.abuse-reports.disable-link', $report) }}" onsubmit="return confirm('Nonaktifkan link?')">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold">Disable Link</button></form>
    @endif
    <form method="POST" action="{{ route('admin.abuse-reports.close', $report) }}">@csrf @method('PATCH')<button class="w-full px-4 py-2 rounded-xl bg-slate-200 text-ink text-sm font-semibold">Close</button></form>
    <form method="POST" action="{{ route('admin.abuse-reports.destroy', $report) }}" onsubmit="return confirm('Hapus laporan?')">@csrf @method('DELETE')<button class="w-full px-4 py-2 rounded-xl bg-slate-100 text-red-700 text-sm font-semibold">Hapus</button></form>
  </div>
</div>
@endsection
