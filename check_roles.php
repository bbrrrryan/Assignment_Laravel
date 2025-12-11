<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;

echo "=== Roles 表数据 ===\n\n";
$roles = Role::all();
foreach ($roles as $role) {
    echo "ID: {$role->id} | 名称: {$role->name} | 显示名: {$role->display_name}\n";
}

echo "\n";
