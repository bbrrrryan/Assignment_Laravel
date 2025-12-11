<?php
/**
 * 快速更新用户角色
 * 使用方法: 
 *   php quick_update_role.php admin 1  (将用户ID 1设为admin)
 *   php quick_update_role.php student 1  (将用户ID 1设为student)
 *   php quick_update_role.php list  (列出所有用户)
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

$action = $argv[1] ?? 'list';
$userId = $argv[2] ?? null;

// 获取角色
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

if ($action == 'list') {
    echo "=== 用户列表 ===\n\n";
    $users = User::with('role')->get();
    foreach ($users as $user) {
        if ($user->role && $user->role instanceof Role) {
            $roleName = $user->role->name;
        } elseif ($user->role_id) {
            $role = Role::find($user->role_id);
            $roleName = $role ? $role->name : '未知';
        } else {
            $roleName = '无角色';
        }
        
        $isAdmin = ($roleName == 'admin') ? ' [管理员]' : '';
        echo "ID: {$user->id} | 名称: {$user->name} | Email: {$user->email} | 角色: {$roleName}{$isAdmin}\n";
    }
    
} elseif ($action == 'admin' && $userId) {
    $user = User::find($userId);
    if (!$user) {
        echo "错误: 用户 ID {$userId} 不存在\n";
        exit(1);
    }
    
    $user->role_id = $adminRole->id;
    $user->save();
    echo "✓ 用户 {$user->name} (ID: {$user->id}) 已设为 admin\n";
    
} elseif ($action == 'student' && $userId) {
    $user = User::find($userId);
    if (!$user) {
        echo "错误: 用户 ID {$userId} 不存在\n";
        exit(1);
    }
    
    $user->role_id = $studentRole->id;
    $user->save();
    echo "✓ 用户 {$user->name} (ID: {$user->id}) 已设为 student\n";
    
} else {
    echo "使用方法:\n";
    echo "  列出所有用户: php quick_update_role.php list\n";
    echo "  设为admin:    php quick_update_role.php admin <用户ID>\n";
    echo "  设为student:  php quick_update_role.php student <用户ID>\n\n";
    echo "示例:\n";
    echo "  php quick_update_role.php admin 1    (将用户1设为管理员)\n";
    echo "  php quick_update_role.php student 2  (将用户2设为学生)\n";
}
