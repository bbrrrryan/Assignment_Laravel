<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserFactory
{
    /**
     * Create a user with role_id instead of role string
     * 
     * @param string|int $type Role name ('admin', 'student', 'staff') or role_id
     * @param string $name
     * @param string $email
     * @param string $password
     * @return User
     */
    public static function makeUser($type, $name, $email, $password)
    {
        // If type is numeric, treat as role_id
        if (is_numeric($type)) {
            $roleId = (int) $type;
        } else {
            // Otherwise, find role by name
            $role = Role::where('name', strtolower($type))->first();
            
            if (!$role) {
                // If role doesn't exist, default to student
                $role = Role::where('name', 'student')->first();
            }
            
            $roleId = $role ? $role->id : null;
        }

        if (!$roleId) {
            throw new \Exception("Invalid role type: {$type}");
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password), 
            'role_id' => $roleId,
            'status' => 'active' 
        ]);
    }
}