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
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'nullable|in:admin,student,staff',
        ]);

        // Check if email already exists
        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            if ($existingUser->status === 'active') {
                // Active account exists, return validation error
                throw ValidationException::withMessages([
                    'email' => ['The email has already been taken.'],
                ]);
            } else if ($existingUser->status === 'inactive') {
                // Handle inactive accounts
                if ($existingUser->otp_expires_at && !$existingUser->otp_expires_at->isPast()) {
                    // Inactive account with valid OTP (pending verification), redirect to OTP verification page
                    return response()->json([
                        'message' => 'OTP already sent. Please check your email and verify.',
                        'redirect_to_otp' => true,
                        'email' => $existingUser->email,
                    ], 200);
                } else if ($existingUser->otp_expires_at && $existingUser->otp_expires_at->isPast()) {
                    // Inactive account with expired OTP (unactivated registration), allow re-registration
                    $existingUser->delete();
                } else {
                    // Inactive account without OTP (deactivated by admin), return friendly error
                    throw ValidationException::withMessages([
                        'email' => ['This account has been deactivated. Please contact administrator to reactivate your account.'],
                    ]);
                }
            }
        }

        // Use provided role or default to student - using simple if-else
        $role = $request->role ?? 'student';
        
        if ($role !== 'admin' && $role !== 'student' && $role !== 'staff') {
            $role = 'student'; // Default to student if invalid
        }
        
        // Generate 6-digit OTP
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = now()->addMinutes(3);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'status' => 'inactive',
            'otp_code' => $otpCode,
            'otp_expires_at' => $otpExpiresAt,
        ];
        
        if ($role === 'student') {
            $userData['personal_id'] = User::generateStudentId();
        } elseif ($role === 'staff') {
            $userData['personal_id'] = User::generateStaffId();
        }
        
        try {
            $user = User::create($userData);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle duplicate entry error (shouldn't happen after our checks, but just in case)
            if ($e->getCode() == 23000) {
                // Check if it's a duplicate email error
                if (strpos($e->getMessage(), 'users_email_unique') !== false) {
                    throw ValidationException::withMessages([
                        'email' => ['This email is already registered. If your account was deactivated, please contact administrator to reactivate it.'],
                    ]);
                }
            }
            // Re-throw if it's a different error
            throw $e;
        }

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

    /**
     * Resend OTP code
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email not found.',
            ], 404);
        }

        // Check if account is already active
        if ($user->status === 'active') {
            return response()->json([
                'message' => 'Account already activated. Please login.',
            ], 400);
        }

        // Generate new 6-digit OTP
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = now()->addMinutes(3);

        // Update user with new OTP
        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => $otpExpiresAt,
        ]);

        // Send OTP email
        try {
            Mail::to($user->email)->send(new OtpVerificationMail($otpCode, $user->name));
        } catch (\Exception $e) {
            Log::error('Failed to resend OTP email: ' . $e->getMessage());
            return response()->json([
                'message' => 'Cannot send OTP email. Please check email configuration.',
                'error' => 'Email sending failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'OTP code resent successfully. Please check your email.',
        ], 200);
    }
}
