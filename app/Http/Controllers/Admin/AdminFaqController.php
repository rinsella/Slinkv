<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AdminFaqController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index()
    {
        $faqs = Faq::orderBy('sort_order')->orderBy('id')->paginate(30);
        return view('admin.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return view('admin.faqs.form', ['faq' => new Faq(['is_active' => true, 'sort_order' => Faq::max('sort_order') + 1])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $faq = Faq::create($data);
        $this->audit->log('faq_create', $faq);
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ dibuat.');
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.form', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $this->validateData($request);
        $faq->update($data);
        $this->audit->log('faq_update', $faq);
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ diperbarui.');
    }

    public function toggle(Faq $faq)
    {
        $faq->update(['is_active' => !$faq->is_active]);
        $this->audit->log('faq_toggle', $faq, null, ['is_active' => $faq->is_active]);
        return back()->with('success', 'Status FAQ diperbarui.');
    }

    public function destroy(Faq $faq)
    {
        $this->audit->log('faq_delete', $faq, ['question' => $faq->question]);
        $faq->delete();
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ dihapus.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:300'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        return $data;
    }
}
