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
            if ($user->otp_code) {
                // Account not verified yet
                return back()->withErrors([
                    'email' => 'Account not activated. Please verify OTP code from email.',
                ])->withInput();
            } else {
                // Account deactivated by admin
                return back()->withErrors([
                    'email' => 'Account is not active. Please contact administrator.',
                ]);
            }
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

        // Create Sanctum token for API calls (profile/settings pages need this)
        $token = $user->createToken('web_session')->plainTextToken;
        
        // Store token in session so frontend can access it
        $request->session()->put('api_token', $token);

        // Set toast message for welcome
        $request->session()->flash('toast_message', 'Welcome ' . $user->name);
        $request->session()->flash('toast_type', 'success');

        // Redirect users based on their role
        $role = strtolower($user->role ?? '');
        
        // Admin and Staff can access admin dashboard
        if ($role === 'admin' || $role === 'staff') {
            return redirect()->route('admin.dashboard');
        } else {
            // Student and other roles go to home
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
            
            // Delete all Sanctum tokens for this user
            $user->tokens()->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Set toast message for logout
        $request->session()->flash('toast_message', 'Success logout');
        $request->session()->flash('toast_type', 'success');

        return redirect()->route('login');
    }

    /**
     * Verify OTP and activate account
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'otp_code' => 'Email not found.',
            ])->withInput();
        }

        // Check if OTP matches
        if ($user->otp_code !== $request->otp_code) {
            return back()->withErrors([
                'otp_code' => 'OTP code wrong.',
            ])->withInput();
        }

        // Check if OTP expired
        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            return back()->withErrors([
                'otp_code' => 'OTP code already expired. Please register again.',
            ])->withInput();
        }

        // Check if already verified
        if ($user->status === 'active') {
            return redirect()->route('login')
                ->with('toast_message', 'Account already activated. Please login.')
                ->with('toast_type', 'info');
        }

        // Activate account
        $user->update([
            'status' => 'active',
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Set toast message
        $request->session()->flash('toast_message', 'Account activated! Can login now');
        $request->session()->flash('toast_type', 'success');

        return redirect()->route('login');
    }
}
