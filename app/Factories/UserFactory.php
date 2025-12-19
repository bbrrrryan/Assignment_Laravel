<?php
/**
 * Author: Liew Zi Li
 */

namespace App\Factories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserFactory
{
    public static function makeUser($type, $name, $email, $password)
    {
        $role = strtolower(trim($type));
        
        if ($role === 'admin' || $role === 'administrator') {
            $roleName = 'admin';
        } elseif ($role === 'student') {
            $roleName = 'student';
        } elseif ($role === 'staff') {
            $roleName = 'staff';
        } else {
            $roleName = 'student';
        }

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password), 
            'role' => $roleName,
            'status' => 'active'
        ];
        
        if ($roleName === 'student') {
            $userData['personal_id'] = User::generateStudentId();
        } elseif ($roleName === 'staff') {
            $userData['personal_id'] = User::generateStaffId();
        }
        
        return User::create($userData);
    }
}
