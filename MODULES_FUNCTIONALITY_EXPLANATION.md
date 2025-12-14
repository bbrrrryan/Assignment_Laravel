# 模块功能详细说明 - User, Profile, Announcement, Notification, Login/Logout, Email

## 📋 目录
1. [User 模块](#1-user-模块)
2. [Profile 模块](#2-profile-模块)
3. [Announcement 模块](#3-announcement-模块)
4. [Notification 模块](#4-notification-模块)
5. [Login/Logout 模块](#5-loginlogout-模块)
6. [Email 模块](#6-email-模块)

---

## 1. User 模块

### 📁 文件位置
- **API Controller**: `app/Http/Controllers/API/UserController.php`
- **Admin Controller**: `app/Http/Controllers/Admin/UserCRUDManagementController.php`
- **Model**: `app/Models/User.php`
- **Factory**: `app/Factories/UserFactory.php`

### 🔧 主要功能

#### 1.1 用户列表 (index)
**方法**: `index(Request $request)`
**位置**: `UserController.php` 第18-64行

**功能特性**:
- ✅ **分页 (Pagination)**: 
  - 使用 `paginate($request->get('per_page', 10))`
  - 默认每页10条记录
  - 可通过 `per_page` 参数自定义
  - 返回分页信息：`current_page`, `last_page`, `per_page`, `total`, `from`, `to`

- ✅ **筛选 (Filtering)**:
  - 按状态筛选：`?status=active` 或 `?status=inactive`
  - 按角色筛选：`?role=admin` 或 `?role=student` 或 `?role=staff`

- ✅ **搜索 (Search)**:
  - 搜索功能：`?search=关键词`
  - 搜索字段：`name` 和 `email`
  - 使用 `LIKE` 查询：`%{$search}%`

- ✅ **排序 (Sorting)**:
  - 排序字段：`sort_by` 参数（id, name, email, role, status, created_at）
  - 排序方向：`sort_order` 参数（asc, desc）
  - 默认：按 `created_at` 降序
  - **SQL注入防护**：白名单验证排序字段

**响应格式**:
```json
{
  "status": "S",
  "message": "Users retrieved successfully",
  "data": {
    "data": [...],
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50,
    "from": 1,
    "to": 10
  },
  "timestamp": "2025-12-15 10:30:00"
}
```

#### 1.2 创建用户 (store)
**方法**: `store(Request $request)`
**位置**: `UserController.php` 第69-115行

**功能特性**:
- ✅ **输入验证 (Input Validation)**:
  - `name`: 必填，最大255字符
  - `email`: 必填，邮箱格式，唯一性验证
  - `password`: 必填，最少6字符
  - `role`: 必填，只能是 admin/student/staff
  - `phone_number`: 可选
  - `address`: 可选
  - `status`: 可选，默认 'active'

- ✅ **密码加密**: 使用 `Hash::make()` 加密密码

- ✅ **Activity Log**: 自动记录创建用户的操作

**响应格式**:
```json
{
  "status": "S",
  "message": "User created successfully",
  "data": {...},
  "timestamp": "2025-12-15 10:30:00"
}
```

#### 1.3 查看用户详情 (show)
**方法**: `show(string $id)`
**位置**: `UserController.php` 第120-132行

**功能特性**:
- ✅ **Eager Loading**: 使用 `with(['activityLogs'])` 预加载活动日志
- ✅ **限制记录**: 只加载最近10条活动日志

#### 1.4 更新用户 (update)
**方法**: `update(Request $request, string $id)`
**位置**: `UserController.php` 第137-183行

**功能特性**:
- ✅ **部分更新**: 使用 `sometimes` 验证规则，只更新提供的字段
- ✅ **Activity Log**: 记录更新操作和变更内容

#### 1.5 删除用户 (destroy)
**方法**: `destroy(string $id)`
**位置**: `UserController.php` 第188-208行

**功能特性**:
- ✅ **Activity Log**: 在删除前记录操作

#### 1.6 用户活动日志 (activityLogs)
**方法**: `activityLogs(string $id, Request $request)`
**位置**: `UserController.php` 第213-227行

**功能特性**:
- ✅ **分页**: 每页15条记录
- ✅ **排序**: 按最新优先 (`latest()`)

#### 1.7 CSV批量上传 (uploadCsv)
**方法**: `uploadCsv(Request $request)`
**位置**: `UserController.php` 第232-372行

**功能特性**:
- ✅ **文件验证**: 
  - 文件类型：csv, txt
  - 最大大小：10MB
- ✅ **CSV解析**: 自动解析CSV文件
- ✅ **密码逻辑**:
  1. 如果有password → 使用password
  2. 如果没有password但有phone_number → 使用phone_number作为password
  3. 如果都没有 → 使用默认密码 "123456"
- ✅ **批量处理**: 使用数据库事务 (`DB::beginTransaction()`)
- ✅ **错误处理**: 记录每行的错误信息
- ✅ **Activity Log**: 记录批量上传操作

**CSV格式**:
```
name,email,password,role,phone_number,address,status
John Doe,john@example.com,password123,student,0123456789,Address,active
```

#### 1.8 导出CSV (exportCsv)
**方法**: `exportCsv()`
**位置**: `UserCRUDManagementController.php` 第139-173行

**功能特性**:
- ✅ **CSV导出**: 导出所有用户数据
- ✅ **文件下载**: 使用 `response()->stream()`
- ✅ **文件名**: 自动生成带时间戳的文件名

---

## 2. Profile 模块

### 📁 文件位置
- **API Controller**: `app/Http/Controllers/API/UserController.php` (updateProfile方法)
- **View**: `resources/views/profile/index.blade.php`

### 🔧 主要功能

#### 2.1 更新个人资料 (updateProfile)
**方法**: `updateProfile(Request $request)`
**位置**: `UserController.php` 第377-418行

**功能特性**:
- ✅ **更新字段**:
  - `name`: 可选更新
  - `phone_number`: 可选更新
  - `address`: 可选更新
  - `password`: 可选更新（需要确认密码 `confirmed`）

- ✅ **密码验证**: 
  - 最少6字符
  - 需要确认密码匹配

- ✅ **Activity Log**: 记录个人资料更新

**响应格式**:
```json
{
  "status": "S",
  "message": "Profile updated successfully",
  "data": {...},
  "timestamp": "2025-12-15 10:30:00"
}
```

#### 2.2 获取自己的活动日志 (myActivityLogs)
**方法**: `myActivityLogs(Request $request)`
**位置**: `UserController.php` 第424-472行

**功能特性**:
- ✅ **限制记录数**: 最多显示30条记录
- ✅ **分页**: 每页10条记录
- ✅ **手动分页**: 自定义分页逻辑（不使用Laravel默认分页器）
- ✅ **排序**: 按最新优先

**响应格式**:
```json
{
  "status": "S",
  "message": "Activity logs retrieved successfully",
  "data": {
    "data": [...],
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 30,
    "from": 1,
    "to": 10
  },
  "timestamp": "2025-12-15 10:30:00"
}
```

---

## 3. Announcement 模块

### 📁 文件位置
- **API Controller**: `app/Http/Controllers/API/AnnouncementController.php`
- **Model**: `app/Models/Announcement.php`
- **Factory**: `app/Factories/AnnouncementFactory.php`

### 🔧 主要功能

#### 3.1 公告列表 (index)
**方法**: `index(Request $request)`
**位置**: `AnnouncementController.php` 第17-52行

**功能特性**:
- ✅ **分页**: 每页10条记录（可自定义）
- ✅ **筛选**:
  - 按类型：`?type=info`
  - 按优先级：`?priority=high`
  - 按激活状态：`?is_active=true`
- ✅ **搜索**: 
  - 搜索字段：`title` 和 `content`
  - 使用 `LIKE` 查询
- ✅ **排序**:
  - 排序字段：id, title, type, priority, created_at, is_active
  - 排序方向：asc, desc
  - 默认：按 `created_at` 降序
  - **SQL注入防护**：白名单验证

#### 3.2 创建公告 (store)
**方法**: `store(Request $request)`
**位置**: `AnnouncementController.php` 第57-90行

**功能特性**:
- ✅ **使用Factory Pattern**: 使用 `AnnouncementFactory::makeAnnouncement()`
- ✅ **输入验证**: 验证所有必填和可选字段
- ✅ **类型验证**: info, warning, success, error, reminder, general

#### 3.3 查看公告 (show)
**方法**: `show(string $id)`
**位置**: `AnnouncementController.php` 第95-106行

**功能特性**:
- ✅ **浏览量统计**: 自动增加 `views_count`
- ✅ **Eager Loading**: 预加载 creator 和 users

#### 3.4 发布公告 (publish)
**方法**: `publish(string $id)`
**位置**: `AnnouncementController.php` 第152-187行

**功能特性**:
- ✅ **目标用户**: 根据 `target_audience` 确定接收用户
- ✅ **多对多关系**: 使用 `sync()` 关联用户
- ✅ **发布时间**: 自动设置 `published_at`

#### 3.5 我的公告 (myAnnouncements)
**方法**: `myAnnouncements(Request $request)`
**位置**: `AnnouncementController.php` 第211-230行

**功能特性**:
- ✅ **分页**: 每页15条记录
- ✅ **筛选**: 按已读/未读状态筛选
- ✅ **排序**: 按关联时间排序

#### 3.6 未读数量 (unreadCount)
**方法**: `unreadCount()`
**位置**: `AnnouncementController.php` 第192-206行

**功能特性**:
- ✅ **实时统计**: 返回当前用户的未读公告数量

---

## 4. Notification 模块

### 📁 文件位置
- **API Controller**: `app/Http/Controllers/API/NotificationController.php`
- **Model**: `app/Models/Notification.php`
- **Factory**: `app/Factories/NotificationFactory.php`

### 🔧 主要功能

#### 4.1 通知列表 (index)
**方法**: `index(Request $request)`
**位置**: `NotificationController.php` 第22-37行

**功能特性**:
- ✅ **分页**: 每页15条记录（可自定义）
- ✅ **筛选**:
  - 按类型：`?type=info`
  - 按优先级：`?priority=urgent`
  - 按激活状态：`?is_active=true`
- ✅ **排序**: 按最新优先 (`latest()`)

#### 4.2 创建通知 (store)
**方法**: `store(Request $request)`
**位置**: `NotificationController.php` 第42-76行

**功能特性**:
- ✅ **使用Factory Pattern**: 使用 `NotificationFactory::makeNotification()`
- ✅ **输入验证**: 完整的验证规则
- ✅ **类型验证**: info, warning, success, error, reminder

#### 4.3 发送通知 (send)
**方法**: `send(string $id)`
**位置**: `NotificationController.php` 第141-181行

**功能特性**:
- ✅ **目标用户筛选**: 根据 `target_audience` 确定接收用户
  - `all`: 所有活跃用户
  - `students`: 所有学生
  - `staff`: 所有员工
  - `admins`: 所有管理员
  - `specific`: 指定的用户ID列表
- ✅ **多对多关联**: 使用 `sync()` 关联用户
- ✅ **状态管理**: 设置 `is_read` 和 `is_acknowledged` 为 false

#### 4.4 我的通知 (myNotifications)
**方法**: `myNotifications(Request $request)`
**位置**: `NotificationController.php` 第186-203行

**功能特性**:
- ✅ **分页**: 每页15条记录
- ✅ **筛选**: 按已读/未读状态筛选
- ✅ **类型筛选**: 按通知类型筛选

#### 4.5 未读数量 (unreadCount)
**方法**: `unreadCount()`
**位置**: `NotificationController.php` 第189-203行

**功能特性**:
- ✅ **实时统计**: 返回当前用户的未读通知数量

#### 4.6 标记已读/未读 (markAsRead / markAsUnread)
**方法**: `markAsRead(string $id)` / `markAsUnread(string $id)`
**位置**: `NotificationController.php` 第208-251行

**功能特性**:
- ✅ **更新Pivot表**: 使用 `updateExistingPivot()` 更新中间表
- ✅ **时间戳**: 自动记录 `read_at` 时间

#### 4.7 确认通知 (acknowledge)
**方法**: `acknowledge(string $id)`
**位置**: `NotificationController.php` 第256-275行

**功能特性**:
- ✅ **确认标记**: 设置 `is_acknowledged` 为 true
- ✅ **时间戳**: 记录 `acknowledged_at`

#### 4.8 获取未读项目 (getUnreadItems)
**方法**: `getUnreadItems(Request $request)`
**位置**: `NotificationController.php` 第281-464行

**功能特性**:
- ✅ **合并数据**: 同时获取公告和通知
- ✅ **筛选**: 按已读/未读筛选
- ✅ **搜索**: 搜索标题和内容
- ✅ **排序**: 
  - 未读优先
  - 星标优先
  - 按日期排序
- ✅ **手动分页**: 自定义分页逻辑
- ✅ **统计**: 返回未读数量

---

## 5. Login/Logout 模块

### 📁 文件位置
- **API Controller**: `app/Http/Controllers/API/AuthController.php`
- **Web Controller**: `app/Http/Controllers/AuthController.php`

### 🔧 主要功能

#### 5.1 注册 (register)
**方法**: `register(Request $request)`
**位置**: `AuthController.php` 第19-98行

**功能特性**:
- ✅ **输入验证**:
  - `name`: 必填，最大255字符
  - `email`: 必填，邮箱格式
  - `password`: 必填，最少6字符，需要确认
  - `role`: 可选，默认 'student'

- ✅ **邮箱唯一性检查**: 检查邮箱是否已存在

- ✅ **OTP验证**:
  - 生成6位数字OTP码
  - OTP有效期：3分钟
  - 账户状态：注册后为 'inactive'，验证后变为 'active'

- ✅ **Email发送**: 使用 `OtpVerificationMail` 发送OTP邮件

- ✅ **错误处理**: 
  - 如果邮件发送失败，删除用户记录
  - 返回详细错误信息

**响应格式**:
```json
{
  "message": "Registration successful. Please check your email for OTP code.",
  "data": {
    "user_id": 123,
    "email": "user@example.com"
  }
}
```

#### 5.2 登录 (login)
**方法**: `login(Request $request)`
**位置**: `AuthController.php` 第103-141行

**功能特性**:
- ✅ **输入验证**: email 和 password

- ✅ **身份验证**:
  - 检查邮箱是否存在
  - 使用 `Hash::check()` 验证密码

- ✅ **账户状态检查**: 只有 'active' 状态的账户可以登录

- ✅ **更新最后登录时间**: `last_login_at = now()`

- ✅ **Activity Log**: 记录登录操作
  - `action`: 'login'
  - `ip_address`: 记录IP地址
  - `user_agent`: 记录浏览器信息

- ✅ **Token生成**: 使用 Laravel Sanctum 生成认证token

**响应格式**:
```json
{
  "message": "Login successful",
  "user": {...},
  "token": "1|xxxxxxxxxxxxx"
}
```

#### 5.3 登出 (logout)
**方法**: `logout(Request $request)`
**位置**: `AuthController.php` 第146-160行

**功能特性**:
- ✅ **Activity Log**: 记录登出操作
  - `action`: 'logout'
  - `ip_address`: 记录IP地址
  - `user_agent`: 记录浏览器信息

- ✅ **Token删除**: 删除当前访问token

**响应格式**:
```json
{
  "message": "Logged out successfully"
}
```

#### 5.4 获取当前用户 (me)
**方法**: `me(Request $request)`
**位置**: `AuthController.php` 第165-172行

**功能特性**:
- ✅ **返回当前认证用户**: 通过token获取用户信息

#### 5.5 重发OTP (resendOtp)
**方法**: `resendOtp(Request $request)`
**位置**: `AuthController.php` 第177-222行

**功能特性**:
- ✅ **生成新OTP**: 重新生成6位数字OTP
- ✅ **更新有效期**: 重新设置3分钟有效期
- ✅ **Email发送**: 重新发送OTP邮件
- ✅ **状态检查**: 如果账户已激活，不允许重发

---

## 6. Email 模块

### 📁 文件位置
- **Mail Class**: `app/Mail/OtpVerificationMail.php`
- **Email View**: `resources/views/emails/otp-verification.blade.php`

### 🔧 主要功能

#### 6.1 OTP验证邮件 (OtpVerificationMail)
**类**: `OtpVerificationMail`
**位置**: `app/Mail/OtpVerificationMail.php`

**功能特性**:
- ✅ **邮件主题**: "Verify Your Account - TARUMT FMS"
- ✅ **邮件内容**: 包含OTP码和用户名
- ✅ **使用Blade模板**: `emails.otp-verification`
- ✅ **传递数据**: 
  - `otpCode`: 6位数字OTP码
  - `userName`: 用户名称

#### 6.2 邮件发送功能
**使用位置**: 
- `AuthController::register()` - 注册时发送OTP
- `AuthController::resendOtp()` - 重发OTP

**功能特性**:
- ✅ **使用Laravel Mail**: `Mail::to($email)->send()`
- ✅ **错误处理**: 
  - 捕获异常
  - 记录错误日志
  - 返回错误响应

---

## 📊 功能总结表

| 模块 | 分页 | 搜索 | 筛选 | 排序 | 验证 | Activity Log | Factory Pattern |
|------|------|------|------|------|------|--------------|-----------------|
| **User** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Profile** | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ | ❌ |
| **Announcement** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Notification** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Login/Logout** | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ |
| **Email** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🔍 详细功能说明

### 分页 (Pagination)

**实现方式**:
```php
// Laravel标准分页
->paginate($request->get('per_page', 10))

// 手动分页（myActivityLogs）
->limit($maxRecords)
->skip($offset)
->take($perPage)
```

**使用位置**:
- User列表：每页10条
- Activity Logs：每页15条
- Notifications：每页15条
- Announcements：每页10条
- My Activity Logs：每页10条（最多30条）

### 搜索 (Search)

**实现方式**:
```php
// LIKE查询
->where('name', 'like', "%{$search}%")
->orWhere('email', 'like', "%{$search}%")
```

**使用位置**:
- User模块：搜索name和email
- Announcement模块：搜索title和content

### 筛选 (Filtering)

**实现方式**:
```php
// 条件筛选
->when($request->status, fn($q) => $q->where('status', $request->status))
->when($request->role, fn($q) => $q->where('role', $request->role))
```

**筛选类型**:
- 按状态：active/inactive
- 按角色：admin/student/staff
- 按类型：info/warning/success/error/reminder
- 按优先级：low/medium/high/urgent
- 按激活状态：true/false

### 排序 (Sorting)

**实现方式**:
```php
// 安全排序（防止SQL注入）
$allowedSortFields = ['id', 'name', 'email', 'role', 'status', 'created_at'];
if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'created_at';
}
->orderBy($sortBy, $sortOrder)
```

**排序字段**:
- User: id, name, email, role, status, created_at
- Announcement: id, title, type, priority, created_at, is_active

### 输入验证 (Input Validation)

**实现方式**:
```php
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'email' => 'required|string|email|max:255|unique:users',
    'password' => 'required|string|min:6',
]);
```

**验证规则**:
- `required`: 必填
- `string`: 字符串类型
- `email`: 邮箱格式
- `max:255`: 最大长度
- `unique:users`: 唯一性验证
- `min:6`: 最小长度
- `confirmed`: 密码确认
- `in:value1,value2`: 枚举值验证

### Activity Log

**实现方式**:
```php
$user->activityLogs()->create([
    'action' => 'create_user',
    'description' => "Created user: {$user->name}",
    'metadata' => ['user_id' => $user->id],
]);
```

**记录的操作**:
- create_user: 创建用户
- update_user: 更新用户
- delete_user: 删除用户
- update_profile: 更新个人资料
- login: 登录
- logout: 登出
- create_booking: 创建预订
- bulk_upload_users: 批量上传用户

### Factory Pattern

**使用位置**:
- `UserFactory::makeUser()` - 创建用户
- `NotificationFactory::makeNotification()` - 创建通知
- `AnnouncementFactory::makeAnnouncement()` - 创建公告

**优势**:
- 统一创建逻辑
- 封装验证和初始化
- 简化Controller代码

---

## 🎯 响应格式 (IFA格式)

所有API响应都遵循IFA格式：

```json
{
  "status": "S",  // S=成功, F=失败, E=错误
  "message": "操作成功",
  "data": {...},
  "timestamp": "2025-12-15 10:30:00"
}
```

**状态码**:
- `200 OK`: 成功GET, PUT
- `201 Created`: 成功POST
- `400 Bad Request`: 无效请求
- `401 Unauthorized`: 未认证
- `403 Forbidden`: 未授权
- `404 Not Found`: 资源不存在
- `422 Unprocessable Entity`: 验证错误
- `500 Internal Server Error`: 服务器错误

---

## 📝 代码示例

### 分页示例
```php
$users = User::query()
    ->where('status', 'active')
    ->paginate($request->get('per_page', 10));
```

### 搜索示例
```php
if ($request->has('search')) {
    $query->where(function($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%");
    });
}
```

### 筛选示例
```php
->when($request->type, fn($q) => $q->where('type', $request->type))
->when($request->priority, fn($q) => $q->where('priority', $request->priority))
```

### 排序示例
```php
$sortBy = $request->get('sort_by', 'created_at');
$sortOrder = $request->get('sort_order', 'desc');
->orderBy($sortBy, $sortOrder)
```

### Activity Log示例
```php
$user->activityLogs()->create([
    'action' => 'login',
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

### Email发送示例
```php
Mail::to($user->email)->send(new OtpVerificationMail($otpCode, $user->name));
```

---

## ✅ 总结

您的代码实现了以下功能：

1. ✅ **完整的分页系统** - 所有列表都支持分页
2. ✅ **搜索功能** - User和Announcement支持搜索
3. ✅ **筛选功能** - 多条件筛选
4. ✅ **安全排序** - 防止SQL注入
5. ✅ **输入验证** - 完整的验证规则
6. ✅ **Activity Log** - 记录所有重要操作
7. ✅ **Factory Pattern** - 统一对象创建
8. ✅ **IFA格式响应** - 标准化的API响应
9. ✅ **Email功能** - OTP验证邮件
10. ✅ **认证系统** - Login/Logout with token

所有功能都已完整实现！🎉

