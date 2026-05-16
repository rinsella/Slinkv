@extends('layouts.admin')
@section('title', $message->subject ?: 'Pesan #'.$message->id)
@section('content')
<a href="{{ route('admin.contact-messages.index') }}" class="text-sm text-primary">← Daftar</a>
<div class="grid lg:grid-cols-3 gap-5 mt-3">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-line p-6">
    <div class="flex items-start justify-between">
      <div>
        <h2 class="text-xl font-bold">{{ $message->subject ?: '(tanpa subjek)' }}</h2>
        <div class="text-sm text-muted">{{ $message->name }} &lt;{{ $message->email }}&gt;</div>
        <div class="text-xs text-muted">{{ $message->created_at?->format('d M Y H:i') }}</div>
      </div>
      <span class="px-2 py-0.5 rounded-full bg-slate-100 text-xs uppercase">{{ $message->status }}</span>
    </div>
    <div class="mt-5 prose max-w-none text-sm whitespace-pre-wrap">{{ $message->message }}</div>
    @if ($message->admin_note)<div class="mt-5 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm"><b>Catatan Admin:</b><div class="whitespace-pre-wrap mt-1">{{ $message->admin_note }}</div></div>@endif
  </div>
  <div class="space-y-3">
    <form method="POST" action="{{ route('admin.contact-messages.replied', $message) }}" class="bg-white rounded-2xl border border-line p-5 space-y-2">@csrf @method('PATCH')
      <label class="text-sm font-medium">Catatan / Balasan</label>
      <textarea name="admin_note" rows="5" class="w-full rounded-xl border-line text-sm">{{ $message->admin_note }}</textarea>
      <button class="w-full px-4 py-2 rounded-xl bg-green-600 text-white text-sm font-semibold">Tandai Sudah Dibalas</button>
    </form>
    <a href="mailto:{{ $message->email }}?subject=Re: {{ urlencode($message->subject) }}" class="block text-center px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Balas via Email</a>
    <form method="POST" action="{{ route('admin.contact-messages.destroy', $message) }}" onsubmit="return confirm('Hapus pesan?')">@csrf @method('DELETE')<button class="w-full px-4 py-2 rounded-xl bg-slate-200 text-red-700 text-sm font-semibold">Hapus</button></form>
  </div>
</div>
@endsection
