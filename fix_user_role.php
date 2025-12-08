<?php
/**
 * Quick script to fix user role
 * Run: php fix_user_role.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "=== Fix User Role Script ===\n\n";

// Get user email from command line or use default
$email = $argv[1] ?? null;

if (!$email) {
    echo "Usage: php fix_user_role.php <email> [role_name]\n";
    echo "Example: php fix_user_role.php admin@example.com admin\n\n";
    
    // List all users
    echo "Current users:\n";
    $users = User::with('role')->get();
    foreach ($users as $user) {
        $roleName = $user->role ? $user->role->name : 'NO ROLE';
        echo "  - {$user->email} (ID: {$user->id}, Role: {$roleName}, role_id: {$user->role_id})\n";
    }
    exit(1);
}

$user = User::where('email', $email)->first();

if (!$user) {
    echo "Error: User with email '{$email}' not found.\n";
    exit(1);
}

$roleName = $argv[2] ?? 'student';

// Get or create role
$role = Role::where('name', $roleName)->first();

if (!$role) {
    echo "Error: Role '{$roleName}' not found. Creating it...\n";
    $role = Role::create([
        'name' => $roleName,
        'display_name' => ucfirst($roleName),
        'description' => ucfirst($roleName) . ' role',
        'is_active' => true,
    ]);
    echo "Created role: {$role->name} (ID: {$role->id})\n";
}

// Assign role to user
$user->role_id = $role->id;
$user->save();
$user->load('role');

echo "\n✓ Success!\n";
echo "User: {$user->email}\n";
echo "Role: {$user->role->name} (ID: {$user->role_id})\n";
echo "Is Admin: " . ($user->isAdmin() ? 'YES' : 'NO') . "\n";
echo "\nPlease clear localStorage and re-login:\n";
echo "1. Open browser console (F12)\n";
echo "2. Run: localStorage.clear()\n";
echo "3. Re-login\n";

