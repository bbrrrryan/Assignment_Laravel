<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{

    public function run(): void
    {
        $roles = [
            [
                'id' => 1,
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'System Administrator with full access',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'student',
                'display_name' => 'Student',
                'description' => 'Student User',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'staff',
                'display_name' => 'Staff',
                'description' => 'Staff Member',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                $role
            );
        }
    }
}

