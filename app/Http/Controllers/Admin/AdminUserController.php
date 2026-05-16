<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = User::query();
        if ($s = $request->get('q')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($role = $request->get('role')) {
            $q->where('role', $role);
        }
        $users = $q->latest()->paginate(20)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load('plan');
        $user->loadCount('shortLinks');
        $payments = $user->payments()->latest()->take(20)->get();
        $subscriptions = $user->subscriptions()->with('plan')->latest()->take(10)->get();
        $links = $user->shortLinks()->latest()->take(10)->get();
        $plans = Plan::orderBy('sort_order')->get();
        return view('admin.users.show', compact('user', 'payments', 'subscriptions', 'links', 'plans'));
    }

    public function edit(User $user)
    {
        $plans = Plan::orderBy('sort_order')->get();
        return view('admin.users.edit', compact('user', 'plans'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'status' => ['required', Rule::in(['active', 'suspended', 'deleted'])],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($user->id === auth()->id() && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak bisa menurunkan role akun sendiri.']);
        }
        if ($user->id === auth()->id() && $data['status'] !== 'active') {
            return back()->withErrors(['status' => 'Anda tidak bisa men-suspend akun sendiri.']);
        }

        $original = $user->only(['name', 'email', 'role', 'status', 'plan_id']);

        $update = collect($data)->except('password')->all();
        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }
        $user->update($update);

        [$old, $new] = $this->audit->diff($original, $user->only(['name', 'email', 'role', 'status', 'plan_id']));
        $this->audit->log('user_update', $user, $old, $new, $user->id);

        return redirect()->route('admin.users.show', $user)->with('success', 'User diperbarui.');
    }

    public function suspend(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['suspend' => 'Tidak bisa men-suspend akun sendiri.']);
        }
        $user->update(['status' => 'suspended']);
        $this->audit->log('user_suspend', $user, null, ['status' => 'suspended'], $user->id);
        return back()->with('success', 'User di-suspend.');
    }

    public function activate(User $user)
    {
        $user->update(['status' => 'active']);
        $this->audit->log('user_activate', $user, null, ['status' => 'active'], $user->id);
        return back()->with('success', 'User diaktifkan.');
    }

    public function changePlan(Request $request, User $user)
    {
        $data = $request->validate(['plan_id' => ['required', 'exists:plans,id']]);
        $old = $user->plan_id;
        $user->update(['plan_id' => $data['plan_id']]);
        $this->audit->log('user_change_plan', $user, ['plan_id' => $old], ['plan_id' => $data['plan_id']], $user->id);
        return back()->with('success', 'Paket user diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'Tidak bisa menghapus akun sendiri.']);
        }
        $this->audit->log('user_delete', $user, $user->only(['name', 'email', 'role']), null, $user->id);
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User dihapus.');
    }
}
