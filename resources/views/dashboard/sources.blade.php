@extends('layouts.dashboard')
@section('content')
<h1 class="text-2xl font-bold mb-1">Sumber Traffic</h1>
<p class="text-muted text-sm mb-6">Breakdown traffic berdasarkan platform asal.</p>

<div class="bg-white rounded-2xl shadow-card border border-line overflow-hidden">
  @if ($sources->isEmpty())
    <div class="p-12 text-center text-muted">
      <div class="text-5xl">📈</div>
      <div class="mt-3 font-semibold text-ink">Belum ada data sumber</div>
      <div class="mt-1 text-sm">Data akan tampil setelah link Anda mendapatkan klik.</div>
    </div>
  @else
    <table class="w-full text-sm">
      <thead class="bg-surface text-muted text-xs"><tr>
        <th class="text-left p-3 font-medium">Source</th><th class="text-right p-3 font-medium">Total</th><th class="text-right p-3 font-medium">Human</th><th class="text-right p-3 font-medium">Bot</th><th class="text-right p-3 font-medium">Bot %</th>
      </tr></thead>
      <tbody class="divide-y divide-line">
      @foreach ($sources as $s)
        @php $pct = $s->total > 0 ? round(($s->bot / $s->total) * 100, 1) : 0; @endphp
        <tr>
          <td class="p-3 font-semibold">{{ $s->source_platform ?: 'Unknown' }}</td>
          <td class="p-3 text-right">{{ number_format($s->total) }}</td>
          <td class="p-3 text-right text-primary">{{ number_format($s->human) }}</td>
          <td class="p-3 text-right text-red-600">{{ number_format($s->bot) }}</td>
          <td class="p-3 text-right {{ $pct > 30 ? 'text-red-600 font-semibold' : 'text-muted' }}">{{ $pct }}%</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
