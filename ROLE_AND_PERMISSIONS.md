# 角色和权限系统说明 (Role and Permissions System)

## 概述

系统使用基于角色的访问控制（RBAC）来区分 **Admin（管理员）** 和 **User（普通用户）**。

## 角色类型

系统支持以下角色：
- **Admin** - 管理员，拥有所有权限
- **Staff** - 员工
- **Student** - 学生

## 如何区分 Admin 和 User

### 1. 后端检查

#### User 模型方法

```php
// 检查是否是管理员
$user->isAdmin();  // 返回 true/false

// 检查是否是员工
$user->isStaff();  // 返回 true/false

// 检查是否是学生
$user->isStudent();  // 返回 true/false
```

#### 中间件保护

在路由中使用 `admin` 中间件来保护管理员专用路由：

```php
Route::middleware('admin')->group(function () {
    // 只有管理员可以访问的路由
});
```

### 2. 前端检查

#### JavaScript API 方法

```javascript
// 检查当前用户是否是管理员
if (API.isAdmin()) {
    // 显示管理员功能
}
```

#### 示例：根据角色显示/隐藏按钮

```javascript
document.addEventListener('DOMContentLoaded', function() {
    if (API.isAdmin()) {
        // 显示管理员专用按钮
        document.getElementById('adminActions').style.display = 'block';
    } else {
        // 隐藏管理员按钮
        document.getElementById('adminActions').style.display = 'none';
    }
});
```

## 权限分配

### Admin（管理员）权限

管理员可以访问所有功能：

✅ **用户管理**
- 查看所有用户列表
- 创建新用户
- 编辑用户信息
- 删除用户
- 上传 CSV 批量导入用户
- 查看用户活动日志

✅ **角色管理**
- 创建、编辑、删除角色
- 分配权限给角色

✅ **设施管理**
- 创建、编辑、删除设施
- 查看设施利用率统计

✅ **预订管理**
- 查看所有预订
- 批准/拒绝预订
- 编辑/删除预订

✅ **通知管理**
- 创建通知
- 发送通知给所有用户或特定用户
- 编辑/删除通知

✅ **反馈管理**
- 查看所有反馈
- 回复反馈
- 屏蔽不当反馈
- 编辑/删除反馈

✅ **忠诚度管理**
- 奖励积分给用户
- 颁发证书

### User（普通用户）权限

普通用户只能访问基本功能：

✅ **个人功能**
- 查看个人资料
- 更新个人资料
- 查看个人预订
- 创建新预订
- 取消自己的预订

✅ **设施浏览**
- 查看设施列表
- 查看设施详情
- 检查设施可用性

✅ **通知**
- 查看收到的通知
- 标记通知为已读
- 确认通知

✅ **反馈**
- 提交反馈
- 查看自己的反馈

✅ **忠诚度**
- 查看自己的积分
- 查看积分历史
- 查看可兑换的奖励
- 兑换奖励
- 查看自己的证书

❌ **不能访问**
- 用户管理
- 角色管理
- 创建/编辑/删除设施
- 批准/拒绝预订
- 创建通知
- 回复反馈
- 奖励积分

## API 路由权限

### 受保护的路由（需要 Admin）

以下路由需要管理员权限：

```
POST   /api/users
PUT    /api/users/{id}
DELETE /api/users/{id}
GET    /api/users/{id}/activity-logs
POST   /api/users/upload-csv

GET    /api/roles
POST   /api/roles
PUT    /api/roles/{id}
DELETE /api/roles/{id}

POST   /api/notifications
PUT    /api/notifications/{id}
DELETE /api/notifications/{id}
POST   /api/notifications/{id}/send

POST   /api/facilities
PUT    /api/facilities/{id}
DELETE /api/facilities/{id}
GET    /api/facilities/{id}/utilization

GET    /api/bookings
PUT    /api/bookings/{id}
DELETE /api/bookings/{id}
PUT    /api/bookings/{id}/approve
PUT    /api/bookings/{id}/reject

GET    /api/feedbacks
PUT    /api/feedbacks/{id}
DELETE /api/feedbacks/{id}
PUT    /api/feedbacks/{id}/respond
PUT    /api/feedbacks/{id}/block

POST   /api/loyalty/points/award
POST   /api/loyalty/certificates/issue
```

### 所有用户可访问的路由

```
GET    /api/me
POST   /api/logout
PUT    /api/users/profile/update

GET    /api/facilities
GET    /api/facilities/{id}
GET    /api/facilities/{id}/availability

POST   /api/bookings
GET    /api/bookings/{id}
PUT    /api/bookings/{id}/cancel
GET    /api/bookings/user/my-bookings
GET    /api/bookings/facility/{facilityId}/availability

GET    /api/notifications/{id}
GET    /api/notifications/user/my-notifications
PUT    /api/notifications/{id}/read
PUT    /api/notifications/{id}/acknowledge

POST   /api/feedbacks
GET    /api/feedbacks/{id}

GET    /api/loyalty/points
GET    /api/loyalty/points/history
GET    /api/loyalty/rewards
POST   /api/loyalty/rewards/redeem
GET    /api/loyalty/certificates
```

## 如何创建 Admin 用户

### 方法 1: 通过数据库

```sql
-- 首先创建 admin 角色（如果不存在）
INSERT INTO roles (name, display_name, description, is_active) 
VALUES ('admin', 'Administrator', 'System Administrator', 1);

-- 创建管理员用户
INSERT INTO users (name, email, password, role_id, status) 
VALUES ('Admin', 'admin@tarumt.edu.my', '$2y$10$...', 1, 'active');
```

### 方法 2: 通过 Seeder

创建数据库种子文件来初始化管理员账户。

### 方法 3: 通过 API（需要先有管理员）

使用 `/api/users` POST 接口创建用户，并设置 `role_id` 为 admin 角色的 ID。

## 前端实现示例

### 显示/隐藏管理员功能

```html
<!-- 在 Blade 模板中 -->
<div id="adminSection" style="display: none;">
    <button onclick="adminFunction()">Admin Only Button</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (API.isAdmin()) {
        document.getElementById('adminSection').style.display = 'block';
    }
});
</script>
```

### 条件渲染表格操作按钮

```javascript
function displayFacilities(facilities) {
    const isAdmin = API.isAdmin();
    
    return facilities.map(facility => `
        <tr>
            <td>${facility.name}</td>
            <td>
                ${isAdmin ? `
                    <button onclick="editFacility(${facility.id})">Edit</button>
                    <button onclick="deleteFacility(${facility.id})">Delete</button>
                ` : ''}
            </td>
        </tr>
    `).join('');
}
```

## 错误处理

当普通用户尝试访问管理员路由时，会收到：

```json
{
    "message": "Unauthorized. Admin access required."
}
```

HTTP 状态码：**403 Forbidden**

## 测试

### 测试 Admin 权限

1. 使用管理员账户登录
2. 尝试访问管理员路由（如 `/api/users`）
3. 应该成功返回数据

### 测试 User 权限

1. 使用普通用户账户登录
2. 尝试访问管理员路由
3. 应该收到 403 错误

## 注意事项

1. **角色检查**：系统通过 `role.name === 'admin'` 或 `role_id === 1` 来判断是否是管理员
2. **默认角色**：新注册的用户默认角色是 `student`
3. **中间件**：所有管理员路由都使用 `admin` 中间件保护
4. **前端验证**：前端检查仅用于 UI 显示，**不能**替代后端验证
5. **安全性**：后端验证是必须的，前端隐藏只是用户体验优化

