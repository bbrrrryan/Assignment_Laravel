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

        // Ensure role is loaded
        $user->load('role');

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Log activity
        $user->activityLogs()->create([
            'action' => 'login',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Login user using session
        Auth::login($user, $request->filled('remember'));

        // Redirect based on role
        // If admin, go to admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } else {
            // If user, go to home page
            return redirect()->route('home');
        }
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        // Log activity
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
