<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDomain;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBlockedDomainController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = BlockedDomain::query();
        if ($s = $request->get('q')) $q->where('domain', 'like', "%{$s}%");
        $domains = $q->latest()->paginate(30)->withQueryString();
        return view('admin.blocked-domains.index', compact('domains'));
    }

    public function create() { return view('admin.blocked-domains.form', ['domain' => new BlockedDomain(['is_active' => true])]); }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $d = BlockedDomain::create($data);
        $this->audit->log('blocked_domain_create', $d);
        return redirect()->route('admin.blocked-domains.index')->with('success', 'Domain diblokir.');
    }

    public function edit(BlockedDomain $blocked_domain) { return view('admin.blocked-domains.form', ['domain' => $blocked_domain]); }

    public function update(Request $request, BlockedDomain $blocked_domain)
    {
        $data = $this->validateData($request, $blocked_domain->id);
        $old = $blocked_domain->only(array_keys($data));
        $blocked_domain->update($data);
        [$o, $n] = $this->audit->diff($old, $blocked_domain->only(array_keys($data)));
        $this->audit->log('blocked_domain_update', $blocked_domain, $o, $n);
        return redirect()->route('admin.blocked-domains.index')->with('success', 'Domain diperbarui.');
    }

    public function toggle(BlockedDomain $blocked_domain)
    {
        $blocked_domain->update(['is_active' => !$blocked_domain->is_active]);
        $this->audit->log('blocked_domain_toggle', $blocked_domain, null, ['is_active' => $blocked_domain->is_active]);
        return back()->with('success', 'Status domain diperbarui.');
    }

    public function destroy(BlockedDomain $blocked_domain)
    {
        $this->audit->log('blocked_domain_delete', $blocked_domain, ['domain' => $blocked_domain->domain]);
        $blocked_domain->delete();
        return redirect()->route('admin.blocked-domains.index')->with('success', 'Domain dihapus.');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:200', Rule::unique('blocked_domains', 'domain')->ignore($id)],
            'reason' => ['nullable', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['domain'] = strtolower(preg_replace('#^https?://|/.*$#', '', trim($data['domain'])));
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        return $data;
    }
}
