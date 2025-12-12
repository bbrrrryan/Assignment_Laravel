<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\OtpVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'nullable|in:admin,student,staff',
        ]);

        // Use provided role or default to student - using simple if-else
        $role = $request->role ?? 'student';
        
        if ($role !== 'admin' && $role !== 'student' && $role !== 'staff') {
            $role = 'student'; // Default to student if invalid
        }
        
        // Generate 6-digit OTP
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = now()->addMinutes(15);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'status' => 'inactive', // Set to inactive until OTP verified
            'otp_code' => $otpCode,
            'otp_expires_at' => $otpExpiresAt,
        ]);

        // Send OTP email - must succeed for security
        try {
            Mail::to($user->email)->send(new OtpVerificationMail($otpCode, $user->name));
        } catch (\Exception $e) {
            // Log detailed error
            $errorMessage = $e->getMessage();
            Log::error('Failed to send OTP email: ' . $errorMessage);
            Log::error('Email error trace: ' . $e->getTraceAsString());
            
            // Delete the user since registration failed
            $user->delete();
            
            // Return error response
            return response()->json([
                'message' => 'Cannot send OTP email. Please check email configuration.',
                'error' => 'Email sending failed: ' . $errorMessage,
            ], 500);
        }

        return response()->json([
            'message' => 'Registration successful. Please check your email for OTP code.',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
            ]
        ], 201);
    }

    /**
     * Login user
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
            return response()->json([
                'message' => 'Account is not active',
            ], 403);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Log activity
        $user->activityLogs()->create([
            'action' => 'login',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Log activity
        $request->user()->activityLogs()->create([
            'action' => 'logout',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => $user,
        ]);
    }
}
