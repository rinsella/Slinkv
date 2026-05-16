@extends('layouts.admin')
@section('title','Health Check')
@section('content')
<div class="bg-white rounded-2xl border border-line overflow-hidden max-w-2xl">
  <table class="w-full text-sm">
    <tbody class="divide-y divide-line">
      @foreach ($checks as $label => [$value, $ok])
        <tr>
          <td class="p-3 font-medium">{{ $label }}</td>
          <td class="p-3 text-muted">{{ $value }}</td>
          <td class="p-3 text-right">@if ($ok)<span class="text-green-600 font-bold">OK</span>@else<span class="text-red-600 font-bold">!</span>@endif</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
