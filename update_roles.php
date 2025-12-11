<?php
/**
 * 简单更新用户角色脚本
 * 使用方法: php update_roles.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "=== 用户角色更新工具 ===\n\n";

// 1. 确保角色存在
$adminRole = Role::firstOrCreate(['name' => 'admin'], [
    'display_name' => 'Administrator',
    'description' => 'System Administrator',
    'is_active' => true,
]);

$studentRole = Role::firstOrCreate(['name' => 'student'], [
    'display_name' => 'Student',
    'description' => 'Student User',
    'is_active' => true,
]);

echo "角色信息:\n";
echo "  - Admin: ID {$adminRole->id}\n";
echo "  - Student: ID {$studentRole->id}\n\n";

// 2. 显示所有用户
echo "当前用户列表:\n";
$users = User::with('role')->get();
foreach ($users as $index => $user) {
    // Check if role is relationship object
    if ($user->relationLoaded('role') && $user->role instanceof Role) {
        $roleName = $user->role->name;
    } elseif ($user->role_id) {
        // Try to load role
        $role = Role::find($user->role_id);
        $roleName = $role ? $role->name : '无角色 (ID: ' . $user->role_id . ')';
    } else {
        $roleName = '无角色';
    }
    echo "  " . ($index + 1) . ". ID: {$user->id}, 名称: {$user->name}, Email: {$user->email}, 当前角色: {$roleName}\n";
}

echo "\n";

// 3. 批量更新选项
echo "选择操作:\n";
echo "  1. 将所有用户设为 student\n";
echo "  2. 将所有用户设为 admin\n";
echo "  3. 手动指定用户\n";
echo "  4. 退出\n\n";

$choice = readline("请输入选项 (1-4): ");

if ($choice == '1') {
    // 所有用户设为 student
    $count = User::where('role_id', '!=', $studentRole->id)
        ->orWhereNull('role_id')
        ->update(['role_id' => $studentRole->id]);
    echo "\n✓ 已将 {$count} 个用户更新为 student\n";
    
} elseif ($choice == '2') {
    // 所有用户设为 admin
    $count = User::update(['role_id' => $adminRole->id]);
    echo "\n✓ 已将 {$count} 个用户更新为 admin\n";
    
} elseif ($choice == '3') {
    // 手动指定
    echo "\n手动更新模式:\n";
    echo "格式: 用户ID,角色名称 (例如: 1,admin 或 2,student)\n\n";
    
    while (true) {
        $input = trim(readline("输入用户ID和角色 (或 'done' 完成): "));
        
        if ($input === 'done' || $input === '') {
            break;
        }
        
        $parts = explode(',', $input);
        if (count($parts) != 2) {
            echo "  ✗ 格式错误，请使用: 用户ID,角色名称\n";
            continue;
        }
        
        $userId = trim($parts[0]);
        $roleName = trim(strtolower($parts[1]));
        
        if ($roleName != 'admin' && $roleName != 'student') {
            echo "  ✗ 角色必须是 'admin' 或 'student'\n";
            continue;
        }
        
        $user = User::find($userId);
        if (!$user) {
            echo "  ✗ 用户 ID {$userId} 不存在\n";
            continue;
        }
        
        if ($roleName == 'admin') {
            $user->role_id = $adminRole->id;
        } else {
            $user->role_id = $studentRole->id;
        }
        
        $user->save();
        echo "  ✓ 用户 {$user->name} (ID: {$user->id}) 已更新为 {$roleName}\n";
    }
    
} else {
    echo "\n退出\n";
    exit;
}

// 4. 显示更新后的结果
echo "\n更新后的用户列表:\n";
$users = User::with('role')->get();
foreach ($users as $index => $user) {
    // Reload role
    $user->load('role');
    
    // Check if role is relationship object
    if ($user->relationLoaded('role') && $user->role instanceof Role) {
        $roleName = $user->role->name;
        $isAdmin = $user->role->name == 'admin' ? ' (管理员)' : '';
    } elseif ($user->role_id) {
        $role = Role::find($user->role_id);
        $roleName = $role ? $role->name : '无角色';
        $isAdmin = ($role && $role->name == 'admin') ? ' (管理员)' : '';
    } else {
        $roleName = '无角色';
        $isAdmin = '';
    }
    
    echo "  " . ($index + 1) . ". ID: {$user->id}, 名称: {$user->name}, Email: {$user->email}, 角色: {$roleName}{$isAdmin}\n";
}

echo "\n=== 完成 ===\n";
