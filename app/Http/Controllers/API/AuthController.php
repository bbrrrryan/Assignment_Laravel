<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'role_id' => 'nullable|exists:roles,id',
        ]);

        // Get or create default student role
        $defaultRole = \App\Models\Role::where('name', 'student')->first();
        
        if (!$defaultRole) {
            // Create student role if it doesn't exist
            $defaultRole = \App\Models\Role::create([
                'name' => 'student',
                'display_name' => 'Student',
                'description' => 'Student User',
                'is_active' => true,
            ]);
        }
        
        // Use provided role_id or default to student
        $roleId = $request->role_id;
        
        // Validate role_id exists if provided
        if ($roleId) {
            $role = \App\Models\Role::find($roleId);
            if (!$role) {
                return response()->json([
                    'message' => 'Invalid role_id provided',
                ], 422);
            }
        } else {
            $roleId = $defaultRole->id;
        }
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $roleId,
            'status' => 'active',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user->load('role'),
            'token' => $token,
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

        // Ensure role is loaded
        $user->load('role');
        
        // If user has no role_id but has role string (from old system), try to match it
        if (!$user->role_id) {
            // Check if role string exists in attributes
            $roleString = $user->getAttribute('role');
            
            if ($roleString) {
                // Find or create role based on string
                $role = \App\Models\Role::where('name', $roleString)->first();
                
                if (!$role) {
                    // Create role if it doesn't exist
                    $role = \App\Models\Role::create([
                        'name' => $roleString,
                        'display_name' => ucfirst($roleString),
                        'description' => ucfirst($roleString) . ' role',
                        'is_active' => true,
                    ]);
                }
                
                $user->role_id = $role->id;
                $user->save();
                $user->load('role');
            } else {
                // If no role string either, assign default student role
                $studentRole = \App\Models\Role::where('name', 'student')->first();
                if ($studentRole) {
                    $user->role_id = $studentRole->id;
                    $user->save();
                    $user->load('role');
                }
            }
        }

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
        
        // Ensure role is loaded
        $user->load('role');
        
        // If user has no role_id but has role string (from old system), try to match it
        if (!$user->role_id) {
            // Check if role string exists in attributes
            $roleString = $user->getAttribute('role');
            
            if ($roleString) {
                // Find or create role based on string
                $role = \App\Models\Role::where('name', $roleString)->first();
                
                if (!$role) {
                    // Create role if it doesn't exist
                    $role = \App\Models\Role::create([
                        'name' => $roleString,
                        'display_name' => ucfirst($roleString),
                        'description' => ucfirst($roleString) . ' role',
                        'is_active' => true,
                    ]);
                }
                
                $user->role_id = $role->id;
                $user->save();
                $user->load('role');
            } else {
                // If no role string either, assign default student role
                $studentRole = \App\Models\Role::where('name', 'student')->first();
                if ($studentRole) {
                    $user->role_id = $studentRole->id;
                    $user->save();
                    $user->load('role');
                }
            }
        }
        
        return response()->json([
            'user' => $user,
        ]);
    }
}
