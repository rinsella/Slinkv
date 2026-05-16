<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminPlanController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index()
    {
        $plans = Plan::withCount('users')->orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.form', ['plan' => new Plan(['is_active' => true, 'billing_period' => 'monthly', 'bot_protection_level' => 'basic'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $plan = Plan::create($data);
        $this->audit->log('plan_create', $plan, null, $data);
        return redirect()->route('admin.plans.index')->with('success', 'Paket dibuat.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validateData($request, $plan->id);
        $old = $plan->only(array_keys($data));
        $plan->update($data);
        [$o, $n] = $this->audit->diff($old, $plan->only(array_keys($data)));
        $this->audit->log('plan_update', $plan, $o, $n);
        return redirect()->route('admin.plans.index')->with('success', 'Paket diperbarui.');
    }

    public function toggle(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        $this->audit->log('plan_toggle', $plan, null, ['is_active' => $plan->is_active]);
        return back()->with('success', 'Status paket diperbarui.');
    }

    public function destroy(Plan $plan)
    {
        if (User::where('plan_id', $plan->id)->exists()) {
            return back()->withErrors(['delete' => 'Paket masih dipakai user. Pindahkan user dulu.']);
        }
        $this->audit->log('plan_delete', $plan, $plan->only(['name', 'slug', 'price']));
        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', 'Paket dihapus.');
    }

    private function validateData(Request $request, ?int $planId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:60', Rule::unique('plans', 'slug')->ignore($planId)],
            'price' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_period' => ['required', Rule::in(['free', 'monthly', 'yearly'])],
            'max_links' => ['nullable', 'integer', 'min:0'],
            'max_clicks_per_link' => ['nullable', 'integer', 'min:0'],
            'analytics_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'bot_protection_level' => ['required', Rule::in(['none', 'basic', 'advanced'])],
            'geo_filter_limit' => ['nullable', 'integer', 'min:0'],
            'has_fallback_url' => ['nullable', 'boolean'],
            'has_custom_alias' => ['nullable', 'boolean'],
            'has_qr_code' => ['nullable', 'boolean'],
            'has_export_csv' => ['nullable', 'boolean'],
            'has_audit_report' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        foreach (['has_fallback_url', 'has_custom_alias', 'has_qr_code', 'has_export_csv', 'has_audit_report', 'is_active'] as $b) {
            $data[$b] = (bool) ($data[$b] ?? false);
        }
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        return $data;
    }
}
