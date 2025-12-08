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
    public static function makeUser($type, $name, $email, $password)
    {
        $role = '';

        if ($type == 'admin') {
            $role = 'admin';
        } elseif ($type == 'student') {
            $role = 'student';
        } elseif ($type == 'staff') {
            $role = 'staff';
        } else {
            $role = 'student';
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password), 
            'role' => $role,
            'status' => 'active' 
        ]);
    }
}