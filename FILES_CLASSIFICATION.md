# 文件分类清单 - User, Notification, Announcement 模块

## 📋 四个主题的文件分类

---

## 1. 🔵 PHP and MySQL

### 实体类 (Models - Eloquent ORM)

**User 模块：**
- `app/Models/User.php` ⭐
  - 使用 Eloquent ORM 与 MySQL 数据库交互
  - 定义表结构和字段（fillable, hidden, casts）
  - 使用对象引用定义关系（hasMany, belongsToMany）
  - 数据库查询方法

**Notification 模块：**
- `app/Models/Notification.php` ⭐
  - Eloquent ORM 模型
  - 数据库表映射
  - 关系定义（belongsTo, belongsToMany）

**Announcement 模块：**
- `app/Models/Announcement.php` ⭐
  - Eloquent ORM 模型
  - 数据库表映射
  - 关系定义（belongsTo, belongsToMany）

### 数据库迁移文件 (Migrations)

**User 模块：**
- `database/migrations/2025_12_08_123005_create_users_table.php` ⭐
  - 创建 users 表结构
  - 定义字段类型和约束

**Notification 模块：**
- `database/migrations/2025_12_08_184703_create_notifications_table.php` ⭐
  - 创建 notifications 表结构
- `database/migrations/2025_12_08_184754_create_user_notification_table.php` ⭐
  - 创建 user_notification 中间表（多对多关系）

**Announcement 模块：**
- `database/migrations/2025_12_13_061343_create_announcements_table.php` ⭐
  - 创建 announcements 表结构
- `database/migrations/2025_12_13_061433_create_user_announcement_table.php` ⭐
  - 创建 user_announcement 中间表（多对多关系）

### 数据库查询操作

**User 模块：**
- `app/Http/Controllers/API/UserController.php`
  - 使用 Eloquent 查询：`User::query()`, `User::create()`, `User::findOrFail()`
  - 关系查询：`$user->activityLogs()`, `with(['activityLogs'])`
  - 数据库事务：`DB::beginTransaction()`, `DB::commit()`

**Notification 模块：**
- `app/Http/Controllers/API/NotificationController.php`
  - Eloquent 查询：`Notification::create()`, `Notification::with()`
  - 关系查询：`$user->notifications()`
  - 数据库查询：`DB::table('user_notification')`

**Announcement 模块：**
- `app/Http/Controllers/API/AnnouncementController.php`
  - Eloquent 查询：`Announcement::create()`, `Announcement::with()`
  - 关系查询

---

## 2. 🟢 Design Patterns

### Factory Pattern (简单工厂模式)

**User 模块：**
- `app/Factories/UserFactory.php` ⭐
  - Simple Factory Pattern 实现
  - `makeUser()` 方法根据角色类型创建用户对象
  - 封装用户创建逻辑

**Notification 模块：**
- `app/Factories/NotificationFactory.php` ⭐
  - Simple Factory Pattern 实现
  - `makeNotification()` 方法根据通知类型创建通知对象
  - 封装通知创建逻辑

**Announcement 模块：**
- `app/Factories/AnnouncementFactory.php` ⭐
  - Simple Factory Pattern 实现
  - `makeAnnouncement()` 方法根据公告类型创建公告对象
  - 封装公告创建逻辑

### 使用位置

**UserFactory 使用：**
- `app/Http/Controllers/PageController.php` (第41行)

---

## 3. 🟡 Secure Coding Practices

### 认证和授权 (Authentication & Authorization)

**中间件：**
- `app/Http/Middleware/AdminMiddleware.php` ⭐
  - 授权检查：防止未授权访问
  - 角色验证：检查用户是否为 admin 或 staff
  - 返回 403 错误给未授权用户

**Controller 中的授权检查：**

**User 模块：**
- `app/Http/Controllers/API/UserController.php`
  - 路由保护：`middleware('admin')` (routes/api.php 第42行)
  - 权限验证：只有管理员可以访问用户管理功能

**Notification 模块：**
- `app/Http/Controllers/API/NotificationController.php`
  - 路由保护：`middleware('admin')` (routes/api.php 第58行)
  - 管理员专用：创建、更新、删除通知

**Announcement 模块：**
- `app/Http/Controllers/API/AnnouncementController.php`
  - 路由保护：`middleware('admin')`
  - 管理员专用：创建、更新、删除公告

### 输入验证 (Input Validation)

**User 模块：**
- `app/Http/Controllers/API/UserController.php`
  - `store()` 方法：验证 name, email, password, role 等字段 (第71-77行)
  - `update()` 方法：验证更新字段 (第141-148行)
  - `uploadCsv()` 方法：CSV 文件验证 (第218-220行)
  - SQL 注入防护：使用白名单验证 sort_by 字段 (第46-48行)

**Notification 模块：**
- `app/Http/Controllers/API/NotificationController.php`
  - `store()` 方法：验证 title, message, type, priority 等字段 (第43-54行)
  - `update()` 方法：验证更新字段 (第91-102行)
  - 枚举值验证：type 只能是 'info', 'warning', 'success', 'error', 'reminder'

**Announcement 模块：**
- `app/Http/Controllers/API/AnnouncementController.php`
  - `store()` 方法：验证所有输入字段 (第58-69行)
  - `update()` 方法：验证更新字段

### 密码安全 (Password Security)

**User 模块：**
- `app/Http/Controllers/API/UserController.php`
  - 密码哈希：`Hash::make($request->password)` (第93行, 第163行)
  - 密码最小长度验证：`min:6` (第74行)

**UserFactory：**
- `app/Factories/UserFactory.php`
  - 密码哈希：`Hash::make($password)` (第44行)

### SQL 注入防护

**User 模块：**
- `app/Http/Controllers/API/UserController.php`
  - 白名单验证：`$allowedSortFields` (第46行)
  - 使用 Eloquent ORM（自动防护 SQL 注入）
  - 参数化查询：所有 Eloquent 方法都使用参数化查询

**Notification/Announcement 模块：**
- 使用 Eloquent ORM（自动防护 SQL 注入）
- 枚举值验证（防止无效数据）

### 数据保护

**User 模型：**
- `app/Models/User.php`
  - `$hidden` 数组：隐藏敏感字段（password, remember_token）(第56-59行)
  - 密码自动哈希：`'password' => 'hashed'` (第65行)

---

## 4. 🔴 Web Service Technologies

### REST API 控制器

**User 模块：**
- `app/Http/Controllers/API/UserController.php` ⭐
  - 暴露的 Web 服务：
    - `GET /api/users` - 获取用户列表
    - `POST /api/users` - 创建用户
    - `GET /api/users/{id}` - 获取单个用户
    - `PUT /api/users/{id}` - 更新用户
    - `DELETE /api/users/{id}` - 删除用户
    - `GET /api/users/{id}/activity-logs` - 获取用户活动日志
    - `POST /api/users/upload-csv` - CSV 批量上传
    - `PUT /api/users/profile/update` - 更新个人资料
    - `GET /api/users/profile/activity-logs` - 获取自己的活动日志

**Notification 模块：**
- `app/Http/Controllers/API/NotificationController.php` ⭐
  - 暴露的 Web 服务：
    - `GET /api/notifications` - 获取通知列表
    - `POST /api/notifications` - 创建通知
    - `GET /api/notifications/{id}` - 获取单个通知
    - `PUT /api/notifications/{id}` - 更新通知
    - `DELETE /api/notifications/{id}` - 删除通知
    - `POST /api/notifications/{id}/send` - 发送通知
    - `GET /api/notifications/user/my-notifications` - 获取我的通知
    - `GET /api/notifications/user/unread-count` - 获取未读数量
    - `PUT /api/notifications/{id}/read` - 标记为已读
    - `PUT /api/notifications/{id}/acknowledge` - 确认通知
    - `GET /api/notifications/user/unread-items` - 获取未读项目

**Announcement 模块：**
- `app/Http/Controllers/API/AnnouncementController.php` ⭐
  - 暴露的 Web 服务：
    - `GET /api/announcements` - 获取公告列表
    - `POST /api/announcements` - 创建公告
    - `GET /api/announcements/{id}` - 获取单个公告
    - `PUT /api/announcements/{id}` - 更新公告
    - `DELETE /api/announcements/{id}` - 删除公告
    - `POST /api/announcements/{id}/publish` - 发布公告

### API 路由定义

- `routes/api.php` ⭐
  - User 路由：第42-49行
  - Notification 路由：第56-74行
  - Announcement 路由：第77-92行
  - 中间件保护：`auth:sanctum`, `admin`

### IFA 格式响应

**所有 API Controller 的响应都包含：**
- `status` 字段：'S' (成功), 'F' (失败), 'E' (错误)
- `timestamp` 字段：'Y-m-d H:i:s' 格式
- `message` 字段：响应消息
- `data` 字段：响应数据

**示例位置：**
- `app/Http/Controllers/API/UserController.php` - 所有 response()->json() (第58-63行等)
- `app/Http/Controllers/API/NotificationController.php` - 所有 response()->json() (第30-35行等)
- `app/Http/Controllers/API/AnnouncementController.php` - 所有 response()->json()

### HTTP 状态码

**使用的状态码：**
- `200 OK` - 成功 GET, PUT 请求
- `201 Created` - 成功 POST 请求
- `400 Bad Request` - 无效请求
- `401 Unauthorized` - 未认证
- `403 Forbidden` - 未授权
- `404 Not Found` - 资源不存在
- `422 Unprocessable Entity` - 验证错误
- `500 Internal Server Error` - 服务器错误

### 认证机制

- Laravel Sanctum Token 认证
- `middleware('auth:sanctum')` - 保护所有 API 路由
- Token 通过 `Authorization: Bearer {token}` 头部传递

---

## 📊 总结表格

| 主题 | User 模块 | Notification 模块 | Announcement 模块 |
|------|-----------|-------------------|-------------------|
| **PHP and MySQL** | ✅ User.php<br>✅ Migrations<br>✅ Eloquent ORM | ✅ Notification.php<br>✅ Migrations<br>✅ Eloquent ORM | ✅ Announcement.php<br>✅ Migrations<br>✅ Eloquent ORM |
| **Design Patterns** | ✅ UserFactory.php | ✅ NotificationFactory.php | ✅ AnnouncementFactory.php |
| **Secure Coding** | ✅ AdminMiddleware<br>✅ Input Validation<br>✅ Password Hashing<br>✅ SQL Injection Prevention | ✅ AdminMiddleware<br>✅ Input Validation<br>✅ Authorization | ✅ AdminMiddleware<br>✅ Input Validation<br>✅ Authorization |
| **Web Services** | ✅ UserController.php<br>✅ REST API<br>✅ IFA Format | ✅ NotificationController.php<br>✅ REST API<br>✅ IFA Format | ✅ AnnouncementController.php<br>✅ REST API<br>✅ IFA Format |

---

## ✅ 完成度检查

### PHP and MySQL ✅
- ✅ 实体类（Models）- 使用 Eloquent ORM
- ✅ 数据库迁移文件（Migrations）
- ✅ 数据库查询操作（Eloquent ORM）
- ✅ 对象引用关系（不是外键）

### Design Patterns ✅
- ✅ UserFactory.php - Simple Factory Pattern
- ✅ NotificationFactory.php - Simple Factory Pattern
- ✅ AnnouncementFactory.php - Simple Factory Pattern

### Secure Coding Practices ✅
- ✅ 认证和授权（AdminMiddleware）
- ✅ 输入验证（所有 Controller）
- ✅ 密码哈希（Hash::make）
- ✅ SQL 注入防护（Eloquent ORM + 白名单验证）
- ✅ 数据保护（$hidden 字段）

### Web Service Technologies ✅
- ✅ REST API 控制器（UserController, NotificationController, AnnouncementController）
- ✅ API 路由定义（routes/api.php）
- ✅ IFA 格式响应（status, timestamp 字段）
- ✅ HTTP 状态码
- ✅ Laravel Sanctum 认证

**所有四个主题都有完整的实现！** ✅

