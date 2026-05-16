<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AdminBlockedIpController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = BlockedIp::query();
        if ($s = $request->get('q')) $q->where('reason', 'like', "%{$s}%")->orWhere('ip_hash', 'like', "%{$s}%");
        $ips = $q->latest()->paginate(30)->withQueryString();
        return view('admin.blocked-ips.index', compact('ips'));
    }

    public function create() { return view('admin.blocked-ips.form', ['ip' => new BlockedIp(['is_active' => true])]); }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $ip = BlockedIp::create($data);
        $this->audit->log('blocked_ip_create', $ip);
        return redirect()->route('admin.blocked-ips.index')->with('success', 'IP diblokir.');
    }

    public function edit(BlockedIp $blocked_ip) { return view('admin.blocked-ips.form', ['ip' => $blocked_ip]); }

    public function update(Request $request, BlockedIp $blocked_ip)
    {
        $data = $this->validateData($request, $blocked_ip->id);
        // Don't allow changing ip_hash on update (re-hash if ip provided)
        $old = $blocked_ip->only(array_keys($data));
        $blocked_ip->update($data);
        [$o, $n] = $this->audit->diff($old, $blocked_ip->only(array_keys($data)));
        $this->audit->log('blocked_ip_update', $blocked_ip, $o, $n);
        return redirect()->route('admin.blocked-ips.index')->with('success', 'IP diperbarui.');
    }

    public function toggle(BlockedIp $blocked_ip)
    {
        $blocked_ip->update(['is_active' => !$blocked_ip->is_active]);
        $this->audit->log('blocked_ip_toggle', $blocked_ip, null, ['is_active' => $blocked_ip->is_active]);
        return back()->with('success', 'Status IP diperbarui.');
    }

    public function destroy(BlockedIp $blocked_ip)
    {
        $this->audit->log('blocked_ip_delete', $blocked_ip);
        $blocked_ip->delete();
        return redirect()->route('admin.blocked-ips.index')->with('success', 'IP dihapus.');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        $rules = [
            'reason' => ['nullable', 'string', 'max:200'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
        if (!$id) {
            $rules['ip'] = ['required', 'ip'];
        } else {
            $rules['ip'] = ['nullable', 'ip'];
        }
        $data = $request->validate($rules);

        $out = [
            'reason' => $data['reason'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
        if (!empty($data['ip'])) {
            $out['ip_hash'] = hash('sha256', $data['ip']);
        }
        return array_filter($out, fn ($v) => $v !== null);
    }
}
