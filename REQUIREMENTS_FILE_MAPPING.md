# 四个要求对应的文件位置说明

## 1. PHP and MySQL

### 数据库迁移文件（Database Migrations）
**位置**: `database/migrations/`
- `2024_12_08_123005_create_users_table.php` - 用户表
- `2024_12_08_184720_create_facilities_table.php` - 设施表
- `2024_12_08_184732_create_bookings_table.php` - 预订表
- `2024_12_08_184703_create_notifications_table.php` - 通知表
- 等等...（共26个迁移文件）

### Eloquent 模型（Models）
**位置**: `app/Models/`
- `User.php` - 用户模型
- `Facility.php` - 设施模型
- `Booking.php` - 预订模型
- `Notification.php` - 通知模型
- `Feedback.php` - 反馈模型
- `LoyaltyPoint.php` - 积分模型
- 等等...

### 数据库操作示例
**文件**: `app/Http/Controllers/Admin/UserCRUDManagementController.php`
- 第223行: `DB::beginTransaction()` - 开始事务
- 第330行: `DB::commit()` - 提交事务
- 第373行: `DB::rollBack()` - 回滚事务

**文件**: `app/Models/User.php`
- 第81-106行: 数据库关系定义（hasMany, belongsToMany）

---

## 2. Design Patterns（设计模式）

### ✅ Factory Pattern（工厂模式）
**文件**: `app/Factories/UserFactory.php`
- **第5行**: 注释明确标注 "Design Pattern: Simple Factory Pattern"
- **第24行**: `makeUser()` 方法 - 根据类型创建不同用户

**使用示例**:
**文件**: `app/Http/Controllers/PageController.php`
- 第41行: `UserFactory::makeUser(...)` - 调用工厂方法

### ✅ MVC Pattern（模型-视图-控制器模式）
**Model（模型）**: `app/Models/`
- `User.php`, `Facility.php`, `Booking.php` 等

**View（视图）**: `resources/views/`
- `admin/users/index.blade.php` - 用户列表视图
- `admin/facilities/index.blade.php` - 设施列表视图
- `notifications/index.blade.php` - 通知列表视图

**Controller（控制器）**: `app/Http/Controllers/`
- `Admin/UserCRUDManagementController.php` - 用户管理控制器
- `Admin/FacilityController.php` - 设施管理控制器
- `API/NotificationController.php` - 通知API控制器

### ✅ Base Controller Pattern（基类控制器模式）
**文件**: `app/Http/Controllers/Admin/AdminBaseController.php`
- **第8行**: `class AdminBaseController extends Controller`
- **第13-41行**: 构造函数中实现权限检查逻辑

**继承此基类的控制器**:
- `app/Http/Controllers/Admin/AdminDashboardController.php` (第15行)
- `app/Http/Controllers/Admin/UserCRUDManagementController.php` (第13行)
- `app/Http/Controllers/Admin/FacilityController.php` (第10行)
- `app/Http/Controllers/Admin/NotificationController.php` (第9行)

### ✅ Middleware Pattern（中间件模式）
**文件**: `app/Http/Middleware/AdminMiddleware.php`
- **第9行**: `class AdminMiddleware`
- **第16行**: `handle()` 方法 - 处理请求前检查权限

**注册位置**: `app/Http/Kernel.php`
- 第59行: `'admin' => \App\Http\Middleware\AdminMiddleware::class`

**使用位置**: `routes/web.php`
- 第35行: `Route::prefix('admin')->middleware('admin')`

### ✅ Repository Pattern（仓储模式 - 隐式实现）
通过 Eloquent ORM 实现，不需要单独的文件：
- **文件**: `app/Models/User.php`
  - 第39行: `class User extends Authenticatable`
  - 使用 Eloquent 方法如 `User::find()`, `User::where()`, `User::create()`

**示例**:
**文件**: `app/Http/Controllers/Admin/UserCRUDManagementController.php`
- 第20行: `User::query()` - 查询构建器
- 第45行: `$query->paginate(15)` - 分页查询

---

## 3. Secure Coding Practices（安全编码实践）

### ✅ 密码加密
**文件**: `app/Http/Controllers/Admin/UserCRUDManagementController.php`
- 第127行: `Hash::make($request->password)` - 密码加密

**文件**: `app/Http/Controllers/API/AuthController.php`
- 第65行: `Hash::make($request->password)` - 注册时加密密码
- 第112行: `Hash::check($request->password, $user->password)` - 登录时验证密码

### ✅ 输入验证
**文件**: `app/Http/Controllers/Admin/UserCRUDManagementController.php`
- 第79-87行: `$validator = Validator::make($request->all(), [...])` - 验证用户输入
- 第183行: `$request->validate(['csv_file' => 'required|mimes:csv,txt|max:2048'])` - 文件验证

**文件**: `app/Http/Controllers/API/AuthController.php`
- 第21-25行: `$request->validate([...])` - 注册验证
- 第105-107行: `$request->validate([...])` - 登录验证

### ✅ CSRF 保护
**文件**: `app/Http/Middleware/VerifyCsrfToken.php`
- Laravel 自动处理 CSRF token

**视图文件**: `resources/views/layouts/app.blade.php`
- Blade 模板自动生成 CSRF token

### ✅ 认证和授权
**文件**: `app/Http/Middleware/AdminMiddleware.php`
- 第18-24行: 检查用户是否登录
- 第30-37行: 检查用户角色（阻止 student 访问）

**文件**: `app/Http/Controllers/Admin/AdminBaseController.php`
- 第15行: `$this->middleware('auth')` - 要求认证
- 第29-37行: 检查用户角色

**文件**: `routes/api.php`
- 第31行: `Route::middleware('auth:sanctum')` - API 认证
- 第41行: `Route::middleware('admin')` - Admin 权限

### ✅ 数据库事务
**文件**: `app/Http/Controllers/Admin/UserCRUDManagementController.php`
- 第223行: `DB::beginTransaction()` - 开始事务
- 第330行: `DB::commit()` - 提交事务
- 第373行: `DB::rollBack()` - 回滚事务（错误时）

**文件**: `app/Http/Controllers/API/UserController.php`
- 第241行: `DB::beginTransaction()`
- 第293行: `DB::commit()`
- 第315行: `DB::rollBack()`

### ✅ SQL 注入防护
通过 Eloquent ORM 自动防护（使用参数化查询）：
**文件**: `app/Http/Controllers/Admin/UserCRUDManagementController.php`
- 第20行: `User::query()` - 使用查询构建器（安全）
- 第25-28行: `$query->where('name', 'like', "%{$search}%")` - 参数化查询

---

## 4. Web Service Technologies（Web 服务技术）

### ✅ RESTful API 路由
**文件**: `routes/api.php`
- 第26-28行: 公开路由（注册、登录）
- 第31-139行: 受保护的路由（需要认证）

**API 端点示例**:
- 第42行: `Route::get('/', [UserController::class, 'index'])` - GET /api/users
- 第43行: `Route::post('/', [UserController::class, 'store'])` - POST /api/users
- 第66行: `Route::get('/user/my-notifications', ...)` - GET /api/notifications/user/my-notifications

### ✅ API 控制器
**位置**: `app/Http/Controllers/API/`
- `AuthController.php` - 认证API
- `UserController.php` - 用户管理API
- `NotificationController.php` - 通知API
- `FacilityController.php` - 设施API
- `BookingController.php` - 预订API
- `FeedbackController.php` - 反馈API
- `LoyaltyController.php` - 积分API

### ✅ Token 认证（Laravel Sanctum）
**文件**: `app/Http/Controllers/API/AuthController.php`
- 第30-35行: 登录时生成 token
- 第36行: `$user->createToken('auth_token')` - 创建 Sanctum token

**文件**: `app/Models/User.php`
- 第37行: `use Laravel\Sanctum\HasApiTokens` - 使用 Sanctum trait
- 第41行: `use HasApiTokens` - 启用 token 功能

### ✅ JSON 响应格式
**文件**: `app/Http/Controllers/API/NotificationController.php`
- 第176-179行: 返回 JSON 格式
```php
return response()->json([
    'message' => 'My notifications retrieved successfully',
    'data' => $notifications,
]);
```

**文件**: `app/Http/Controllers/API/AuthController.php`
- 第37-42行: 登录成功返回 JSON
- 第113-116行: 登录失败返回 JSON

### ✅ HTTP 状态码
**文件**: `app/Http/Middleware/AdminMiddleware.php`
- 第20-22行: 返回 401 Unauthorized
- 第31-33行: 返回 403 Forbidden

**文件**: `app/Http/Controllers/API/AuthController.php`
- 第115行: `return response()->json([...], 401)` - 401 未授权
- 第42行: `return response()->json([...], 201)` - 201 创建成功

---

## 快速查找指南

### 要找 Design Pattern？
1. **Factory Pattern**: `app/Factories/UserFactory.php` ⭐（最明显）
2. **MVC Pattern**: 
   - Model: `app/Models/`
   - View: `resources/views/`
   - Controller: `app/Http/Controllers/`
3. **Base Controller**: `app/Http/Controllers/Admin/AdminBaseController.php`
4. **Middleware**: `app/Http/Middleware/AdminMiddleware.php`

### 要找 PHP and MySQL？
1. **数据库迁移**: `database/migrations/`
2. **模型**: `app/Models/User.php`
3. **数据库操作**: `app/Http/Controllers/Admin/UserCRUDManagementController.php` (第223-330行)

### 要找 Secure Coding？
1. **密码加密**: `app/Http/Controllers/API/AuthController.php` (第65, 112行)
2. **输入验证**: `app/Http/Controllers/Admin/UserCRUDManagementController.php` (第79行)
3. **权限检查**: `app/Http/Middleware/AdminMiddleware.php`

### 要找 Web Service？
1. **API 路由**: `routes/api.php`
2. **API 控制器**: `app/Http/Controllers/API/`
3. **Token 认证**: `app/Http/Controllers/API/AuthController.php` (第30-36行)

---

## 总结

**Design Pattern 最明显的文件**:
- ✅ `app/Factories/UserFactory.php` - 第5行明确标注 "Design Pattern: Simple Factory Pattern"
- ✅ `app/Http/Controllers/Admin/AdminBaseController.php` - Base Controller Pattern
- ✅ `app/Http/Middleware/AdminMiddleware.php` - Middleware Pattern

**其他三个要求都很明显，分布在多个文件中。**

