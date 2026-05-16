<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = Payment::with(['user', 'plan']);
        if ($s = $request->get('status')) $q->where('status', $s);
        if ($search = $request->get('q')) {
            $q->where(fn ($w) => $w->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")));
        }
        $payments = $q->latest()->paginate(30)->withQueryString();
        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['user', 'plan', 'subscription']);
        return view('admin.payments.show', compact('payment'));
    }

    public function markPaid(Payment $payment)
    {
        // Idempotent: jangan proses dua kali
        if ($payment->status === 'paid') {
            return back()->with('success', 'Pembayaran sudah paid sebelumnya.');
        }

        $payment->update(['status' => 'paid', 'paid_at' => now()]);

        $plan = $payment->plan;
        $billing = $plan?->billing_period;
        $expires = match ($billing) {
            'yearly' => now()->addYear(),
            'monthly' => now()->addMonth(),
            default => ($plan && (float) $plan->price <= 0) ? null : now()->addMonth(),
        };

        // Buat subscription jika belum ada
        $subscription = $payment->subscription_id ? Subscription::find($payment->subscription_id) : null;
        if (!$subscription) {
            $subscription = Subscription::create([
                'user_id' => $payment->user_id,
                'plan_id' => $payment->plan_id,
                'status' => 'pending',
                'payment_gateway' => $payment->gateway ?: 'manual',
                'payment_reference' => $payment->invoice_number,
            ]);
            $payment->update(['subscription_id' => $subscription->id]);
        }

        // Cancel/expire subscription active user lain agar tidak dobel aktif
        Subscription::where('user_id', $payment->user_id)
            ->where('status', 'active')
            ->where('id', '!=', $subscription->id)
            ->update(['status' => 'expired']);

        // Aktifkan subscription baru
        $subscription->update([
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => $expires,
        ]);

        // Update user plan
        User::where('id', $payment->user_id)->update(['plan_id' => $payment->plan_id]);

        $this->audit->log('payment_mark_paid', $payment, null, ['status' => 'paid', 'subscription_id' => $subscription->id], $payment->user_id);
        return back()->with('success', 'Pembayaran ditandai paid & paket aktif.');
    }

    public function markFailed(Payment $payment)
    {
        $payment->update(['status' => 'failed']);
        $this->audit->log('payment_mark_failed', $payment, null, ['status' => 'failed'], $payment->user_id);
        return back()->with('success', 'Pembayaran ditandai failed.');
    }

    public function markExpired(Payment $payment)
    {
        $payment->update(['status' => 'expired']);
        $this->audit->log('payment_mark_expired', $payment, null, ['status' => 'expired'], $payment->user_id);
        return back()->with('success', 'Pembayaran ditandai expired.');
    }

    public function refund(Payment $payment)
    {
        $payment->update(['status' => 'refunded']);
        $this->audit->log('payment_refund', $payment, null, ['status' => 'refunded'], $payment->user_id);
        return back()->with('success', 'Pembayaran di-refund.');
    }
}
