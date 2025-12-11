<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserFactory
{
    /**
     * Create a user with role string
     * 
     * @param string $type Role name ('admin', 'student', 'staff')
     * @param string $name
     * @param string $email
     * @param string $password
     * @return User
     */
    public static function makeUser($type, $name, $email, $password)
    {
        // Normalize role name
        $role = strtolower(trim($type));
        
        // Validate role - using simple if-else
        if ($role === 'admin' || $role === 'administrator') {
            $roleName = 'admin';
        } elseif ($role === 'student') {
            $roleName = 'student';
        } elseif ($role === 'staff') {
            $roleName = 'staff';
        } else {
            // Default to student if invalid
            $roleName = 'student';
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password), 
            'role' => $roleName,
            'status' => 'active' 
        ]);
    }
}