<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSubscriptionController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = Subscription::with(['user', 'plan']);
        if ($s = $request->get('status')) $q->where('status', $s);
        if ($email = $request->get('q')) {
            $q->whereHas('user', fn ($u) => $u->where('email', 'like', "%{$email}%"));
        }
        $subscriptions = $q->latest()->paginate(30)->withQueryString();
        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'plan']);
        $payments = $subscription->payments()->latest()->get();
        return view('admin.subscriptions.show', compact('subscription', 'payments'));
    }

    public function edit(Subscription $subscription)
    {
        $plans = Plan::orderBy('sort_order')->get();
        return view('admin.subscriptions.edit', compact('subscription', 'plans'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', Rule::in(['active', 'expired', 'cancelled', 'pending'])],
            'started_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);
        $old = $subscription->only(array_keys($data));
        $subscription->update($data);
        if ($data['status'] === 'active') {
            User::where('id', $subscription->user_id)->update(['plan_id' => $data['plan_id']]);
        }
        [$o, $n] = $this->audit->diff($old, $subscription->only(array_keys($data)));
        $this->audit->log('subscription_update', $subscription, $o, $n, $subscription->user_id);
        return redirect()->route('admin.subscriptions.show', $subscription)->with('success', 'Subscription diperbarui.');
    }

    public function activate(Subscription $subscription)
    {
        $subscription->update(['status' => 'active', 'started_at' => $subscription->started_at ?? now()]);
        User::where('id', $subscription->user_id)->update(['plan_id' => $subscription->plan_id]);
        $this->audit->log('subscription_activate', $subscription, null, null, $subscription->user_id);
        return back()->with('success', 'Subscription diaktifkan.');
    }

    public function cancel(Subscription $subscription)
    {
        $subscription->update(['status' => 'cancelled']);
        $this->audit->log('subscription_cancel', $subscription, null, null, $subscription->user_id);
        return back()->with('success', 'Subscription dibatalkan.');
    }

    public function extend(Request $request, Subscription $subscription)
    {
        $period = $request->input('period', 'month'); // 'month' | 'year'
        $base = $subscription->expires_at ?: now();
        $newExpires = $period === 'year' ? $base->copy()->addYear() : $base->copy()->addMonth();
        $subscription->update(['expires_at' => $newExpires, 'status' => 'active']);
        $this->audit->log('subscription_extend', $subscription, null, ['expires_at' => $newExpires->toIso8601String(), 'by' => $period], $subscription->user_id);
        return back()->with('success', "Subscription diperpanjang 1 {$period}.");
    }
}
