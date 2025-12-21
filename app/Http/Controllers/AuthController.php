<?php
/**
 * Author: Liew Zi Li
 */

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
   
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
                return back()->withErrors([
                    'email' => 'Account not activated. Please verify OTP code from email.',
                ])->withInput();
            } else {
                return back()->withErrors([
                    'email' => 'Account is not active. Please contact administrator.',
                ]);
            }
        }

        $user->update(['last_login_at' => now()]);

        $user->activityLogs()->create([
            'action' => 'login',
            'ip_address' => $request->ip(),
        ]);

        Auth::login($user, $request->filled('remember'));

        $token = $user->createToken('web_session')->plainTextToken;
        
        $request->session()->put('api_token', $token);

        $request->session()->flash('toast_message', 'Welcome ' . $user->name);
        $request->session()->flash('toast_type', 'success');

        $role = strtolower($user->role ?? '');
        
        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('home');
        }
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->activityLogs()->create([
                'action' => 'logout',
                'ip_address' => $request->ip(),
            ]);
            
            $user->tokens()->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        $request->session()->flash('toast_message', 'Success logout');
        $request->session()->flash('toast_type', 'success');

        return redirect()->route('login');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        $emailFromUrl = $request->query('email');
        $emailFromForm = $request->input('email');
        
        $emailToVerify = $emailFromUrl ?: $emailFromForm;

        $user = User::where('email', $emailToVerify)->first();

        if (!$user) {
            return back()->withErrors([
                'otp_code' => 'Email not found.',
            ])->withInput();
        }
        
        if ($emailFromForm && $emailFromForm !== $user->email) {
            return back()->withErrors([
                'email' => 'Email cannot be changed during verification.',
            ])->withInput();
        }
        
        if ($emailFromUrl && $emailFromForm && $emailFromUrl !== $emailFromForm) {
            return back()->withErrors([
                'email' => 'Email mismatch detected. Please use the email from the verification link.',
            ])->withInput();
        }

        if ($user->otp_code !== $request->otp_code) {
            return back()->withErrors([
                'otp_code' => 'OTP code wrong.',
            ])->withInput();
        }

        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            return back()->withErrors([
                'otp_code' => 'OTP code already expired. Please resend OTP.',
            ])->withInput();
        }

        if ($user->status === 'active') {
            return redirect()->route('login')
                ->with('toast_message', 'Account already activated. Please login.')
                ->with('toast_type', 'info');
        }

        $user->update([
            'status' => 'active',
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        $request->session()->flash('toast_message', 'Account activated! Can login now');
        $request->session()->flash('toast_type', 'success');

        return redirect()->route('login');
    }
}
