<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // The User model casts `password` to 'hashed', so we pass the plain
        // value and let Laravel hash it. A manual Hash::make() here would be
        // redundant (and fragile) given the cast.
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
        ]);

        Auth::login($user);

        // Record registration in the audit trail for consistency with the
        // other create operations across the system.
        try {
            AuditTrail::create([
                'user_id' => $user->id,
                'action' => 'register',
                'model_type' => User::class,
                'model_id' => $user->id,
                'new_values' => ['name' => $user->name, 'email' => $user->email],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log registration audit', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route(Auth::user()->role === 'user'
            ? 'strategic-advisor.index'
            : 'dashboard');
    }
}
