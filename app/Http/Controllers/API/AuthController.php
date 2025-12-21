<?php
/**
 * Author: Liew Zi Li
 */
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
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'nullable|in:admin,student,staff',
        ]);

        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            if ($existingUser->status === 'active') {
                throw ValidationException::withMessages([
                    'email' => ['The email has already been taken.'],
                ]);
            } else if ($existingUser->status === 'inactive') {
                if ($existingUser->otp_expires_at && !$existingUser->otp_expires_at->isPast()) {
                    return response()->json([
                        'status' => 'S',
                        'message' => 'OTP already sent. Please check your email and verify.',
                        'redirect_to_otp' => true,
                        'email' => $existingUser->email,
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                    ], 200);
                } else if ($existingUser->otp_expires_at && $existingUser->otp_expires_at->isPast()) {
                    $existingUser->delete();
                } else {
                    throw ValidationException::withMessages([
                        'email' => ['This account has been deactivated. Please contact administrator to reactivate your account.'],
                    ]);
                }
            }
        }

        $role = $request->role ?? 'student';
        
        if ($role !== 'admin' && $role !== 'student' && $role !== 'staff') {
            $role = 'student';
        }
        
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
            if ($e->getCode() == 23000) {
                if (strpos($e->getMessage(), 'users_email_unique') !== false) {
                    throw ValidationException::withMessages([
                        'email' => ['This email is already registered. If your account was deactivated, please contact administrator to reactivate it.'],
                    ]);
                }
            }
            throw $e;
        }

        try {
            Mail::to($user->email)->send(new OtpVerificationMail($otpCode, $user->name));
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Failed to send OTP email: ' . $errorMessage);
            Log::error('Email error trace: ' . $e->getTraceAsString());
            
            $user->delete();
            
            return response()->json([
                'status' => 'E',
                'message' => 'Cannot send OTP email. Please check email configuration.',
                'error' => 'Email sending failed: ' . $errorMessage,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }

        return response()->json([
            'status' => 'S',
            'message' => 'Registration successful. Please check your email for OTP code.',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 201);
    }

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
                'status' => 'F',
                'message' => 'Account is not active',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        $user->activityLogs()->create([
            'action' => 'login',
            'ip_address' => $request->ip(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'S',
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->activityLogs()->create([
            'action' => 'logout',
            'ip_address' => $request->ip(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'S',
            'message' => 'Logged out successfully',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'S',
            'user' => $user,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'F',
                'message' => 'Email not found.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 404);
        }

        if ($user->status === 'active') {
            return response()->json([
                'status' => 'F',
                'message' => 'Account already activated. Please login.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 400);
        }

        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = now()->addMinutes(3);

        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => $otpExpiresAt,
        ]);

        try {
            Mail::to($user->email)->send(new OtpVerificationMail($otpCode, $user->name));
        } catch (\Exception $e) {
            Log::error('Failed to resend OTP email: ' . $e->getMessage());
            return response()->json([
                'status' => 'E',
                'message' => 'Cannot send OTP email. Please check email configuration.',
                'error' => 'Email sending failed: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }

        return response()->json([
            'status' => 'S',
            'message' => 'OTP code resent successfully. Please check your email.',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 200);
    }
}
