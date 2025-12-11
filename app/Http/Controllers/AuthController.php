<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 * Web Authentication Controller
 */

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return back()->withErrors([
                'email' => 'Your account is not active. Please contact administrator.',
            ]);
        }

        // Track last login time
        $user->update(['last_login_at' => now()]);

        // Log the login activity
        $user->activityLogs()->create([
            'action' => 'login',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Authenticate user and create session
        Auth::login($user, $request->filled('remember'));

        // Redirect users based on their role
        $role = strtolower($user->role ?? '');
        
        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($role === 'student') {
            return redirect()->route('home');
        } else {
            // Fallback to home for any other roles
            return redirect()->route('home');
        }
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        // Log the logout activity before destroying session
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $user->activityLogs()->create([
                'action' => 'logout',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
