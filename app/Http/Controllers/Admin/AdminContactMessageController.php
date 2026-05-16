<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AdminContactMessageController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = ContactMessage::query();
        if ($s = $request->get('q')) $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('subject', 'like', "%{$s}%"));
        if ($status = $request->get('status')) $q->where('status', $status);
        $messages = $q->latest()->paginate(20)->withQueryString();
        return view('admin.contact-messages.index', compact('messages'));
    }

    public function show(ContactMessage $message)
    {
        if ($message->status === 'unread') {
            $message->update(['status' => 'read']);
        }
        return view('admin.contact-messages.show', compact('message'));
    }

    public function markRead(ContactMessage $message)
    {
        $message->update(['status' => 'read']);
        return back()->with('success', 'Ditandai sudah dibaca.');
    }

    public function markReplied(Request $request, ContactMessage $message)
    {
        $request->validate(['admin_note' => ['nullable', 'string', 'max:2000']]);
        $message->update([
            'status' => 'replied',
            'admin_note' => $request->input('admin_note', $message->admin_note),
        ]);
        $this->audit->log('contact_message_replied', $message);
        return back()->with('success', 'Ditandai sudah dibalas.');
    }

    public function destroy(ContactMessage $message)
    {
        $this->audit->log('contact_message_delete', $message, ['email' => $message->email]);
        $message->delete();
        return redirect()->route('admin.contact-messages.index')->with('success', 'Pesan dihapus.');
    }
}
