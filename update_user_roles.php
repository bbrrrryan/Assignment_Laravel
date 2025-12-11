<?php
/**
 * 更新用户角色脚本
 * 将用户角色更新为 student 或 admin
 * 使用方法: php update_user_roles.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "=== 更新用户角色 ===\n\n";

// 1. 确保角色存在
echo "1. 检查角色...\n";
$adminRole = Role::firstOrCreate(
    ['name' => 'admin'],
    [
        'display_name' => 'Administrator',
        'description' => 'System Administrator with full access',
        'is_active' => true,
    ]
);

$studentRole = Role::firstOrCreate(
    ['name' => 'student'],
    [
        'display_name' => 'Student',
        'description' => 'Student User',
        'is_active' => true,
    ]
);

echo "   - Admin Role ID: {$adminRole->id}\n";
echo "   - Student Role ID: {$studentRole->id}\n\n";

// 2. 显示当前用户
echo "2. 当前用户列表:\n";
$users = User::with('role')->get();
foreach ($users as $user) {
    $roleName = $user->role ? $user->role->name : 'NULL';
    echo "   - ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Role: {$roleName} (role_id: {$user->role_id})\n";
}

echo "\n";
echo "3. 请输入要更新的用户信息:\n";
echo "   格式: user_id,role_name (例如: 1,admin 或 2,student)\n";
echo "   输入 'done' 完成，输入 'all_student' 将所有用户设为 student\n";
echo "   输入 'all_admin' 将所有用户设为 admin\n\n";

$updates = [];
while (true) {
    $input = trim(readline("请输入 (或 'done' 完成): "));
    
    if ($input === 'done') {
        break;
    }
    
    if ($input === 'all_student') {
        // 更新所有用户为 student
        $updated = User::where('role_id', '!=', $studentRole->id)
            ->orWhereNull('role_id')
            ->update(['role_id' => $studentRole->id]);
        echo "   ✓ 已将 {$updated} 个用户更新为 student\n\n";
        break;
    }
    
    if ($input === 'all_admin') {
        // 更新所有用户为 admin
        $updated = User::update(['role_id' => $adminRole->id]);
        echo "   ✓ 已将 {$updated} 个用户更新为 admin\n\n";
        break;
    }
    
    // 解析输入
    $parts = explode(',', $input);
    if (count($parts) !== 2) {
        echo "   ✗ 格式错误，请使用: user_id,role_name\n";
        continue;
    }
    
    $userId = trim($parts[0]);
    $roleName = trim(strtolower($parts[1]));
    
    if (!in_array($roleName, ['admin', 'student'])) {
        echo "   ✗ 角色必须是 'admin' 或 'student'\n";
        continue;
    }
    
    $user = User::find($userId);
    if (!$user) {
        echo "   ✗ 用户 ID {$userId} 不存在\n";
        continue;
    }
    
    $targetRole = $roleName === 'admin' ? $adminRole : $studentRole;
    $user->role_id = $targetRole->id;
    $user->save();
    
    echo "   ✓ 用户 {$user->name} (ID: {$user->id}) 已更新为 {$roleName}\n\n";
}

// 4. 显示更新后的用户
echo "\n4. 更新后的用户列表:\n";
$users = User::with('role')->get();
foreach ($users as $user) {
    $roleName = $user->role ? $user->role->name : 'NULL';
    echo "   - ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Role: {$roleName}\n";
}

echo "\n=== 完成 ===\n";
