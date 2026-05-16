@extends('layouts.admin')
@section('title','Health Check')
@section('content')
<div class="bg-white rounded-2xl border border-line p-6">
  <h2 class="text-lg font-bold mb-4">System Health</h2>
  <table class="w-full text-sm">
    <thead class="text-xs text-muted"><tr><th class="text-left p-2">Check</th><th class="text-left p-2">Status</th><th class="text-left p-2">Detail</th></tr></thead>
    <tbody class="divide-y divide-line">
    @foreach ($checks as $name => $check)
      @php [$value, $ok] = $check; @endphp
      <tr>
        <td class="p-2 font-semibold">{{ $name }}</td>
        <td class="p-2"><span class="px-2 py-0.5 rounded-full text-xs {{ $ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $ok ? 'OK' : 'WARN' }}</span></td>
        <td class="p-2 text-xs text-muted">{{ $value }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endsection
