<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Anda harus menyetujui Syarat & Privasi.',
        ]);

        $freePlan = Plan::where('slug', 'free')->first();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'plan_id' => $freePlan?->id,
            'referral_code' => Str::lower(Str::random(8)),
            'role' => 'user',
            'status' => 'active',
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        // Pending shortlink from landing page form
        if ($url = $request->session()->pull('pending_destination_url')) {
            return redirect()->route('dashboard.links.create')->with('prefill_url', $url);
        }

        return redirect()->intended(route('dashboard.index'))->with('success', 'Selamat datang di SlinkV!');
    }
}
