# Feedback模块使用Booking Web Service实现说明

## 📋 实现概述

已在Feedback模块中实现了使用Booking Web Service获取booking详情的功能。

---

## ✅ 实现内容

### 1. 新增方法：`getBookingDetailsForFeedback()`

**文件位置**: `app/Http/Controllers/API/FeedbackController.php`

**功能**:
- 获取与feedback关联的booking详细信息
- 使用Booking Module的web service (`/api/bookings/service/get-info`)
- 符合IFA标准（包含timestamp/requestID验证）
- 包含错误处理和fallback机制

**方法签名**:
```php
public function getBookingDetailsForFeedback(Request $request, string $id)
```

---

## 🔧 实现细节

### 1. IFA标准合规

**请求验证**:
```php
// 验证必须包含timestamp或requestID
if (!$request->has('timestamp') && !$request->has('requestID')) {
    return response()->json([
        'status' => 'F',
        'message' => 'Validation error: timestamp or requestID is mandatory',
        'errors' => [
            'timestamp' => 'Either timestamp or requestID must be provided',
        ],
        'timestamp' => now()->format('Y-m-d H:i:s'),
    ], 422);
}
```

**响应格式**:
- 所有响应都包含`status`字段（S/F/E）
- 所有响应都包含`timestamp`字段
- 符合IFA标准要求

### 2. 权限检查

```php
// 检查用户是否有权限查看此feedback
$user = $request->user();
if (!$user->isAdmin() && $feedback->user_id !== $user->id) {
    return response()->json([
        'status' => 'F',
        'message' => 'Unauthorized. You can only view booking details for your own feedbacks.',
        'timestamp' => now()->format('Y-m-d H:i:s'),
    ], 403);
}
```

### 3. Booking关联检查

```php
// 检查feedback是否关联到booking
if (!$feedback->booking_id) {
    return response()->json([
        'status' => 'F',
        'message' => 'This feedback is not related to a booking',
        'data' => [
            'feedback' => [
                'id' => $feedback->id,
                'subject' => $feedback->subject,
                'type' => $feedback->type,
            ],
        ],
        'timestamp' => now()->format('Y-m-d H:i:s'),
    ], 404);
}
```

### 4. Web Service调用

```php
// 调用Booking Module的web service
$baseUrl = config('app.url', 'http://localhost:8000');
$apiUrl = rtrim($baseUrl, '/') . '/api/bookings/service/get-info';

$response = Http::timeout(10)->post($apiUrl, [
    'booking_id' => $feedback->booking_id,
    'timestamp' => now()->format('Y-m-d H:i:s'),
]);
```

### 5. Fallback机制

如果web service调用失败，会自动fallback到直接数据库查询：

```php
// Fallback to direct query if web service fails
$booking = \App\Models\Booking::with(['user', 'facility', 'attendees', 'slots'])
    ->findOrFail($feedback->booking_id);
```

### 6. 错误处理和日志

- 记录所有web service调用失败的情况
- 记录异常信息用于调试
- 不中断用户操作，提供fallback

---

## 🛣️ 路由配置

**文件位置**: `routes/api.php`

**路由定义**:
```php
Route::prefix('feedbacks')->group(function () {
    // ...
    Route::get('/{id}/booking-details', [FeedbackController::class, 'getBookingDetailsForFeedback']); 
    // Must be before /{id} to avoid route conflict
    Route::get('/{id}', [FeedbackController::class, 'show']);
    // ...
});
```

**路由路径**: `GET /api/feedbacks/{id}/booking-details`

**认证要求**: 需要`auth:sanctum`中间件（已在路由组中配置）

---

## 📝 API使用示例

### 请求示例

**使用cURL**:
```bash
curl -X GET "http://localhost:8000/api/feedbacks/123/booking-details?timestamp=2024-01-15%2014:30:00" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**使用JavaScript (Fetch API)**:
```javascript
async function getBookingDetailsForFeedback(feedbackId) {
    const response = await fetch(`/api/feedbacks/${feedbackId}/booking-details?timestamp=${new Date().toISOString().slice(0, 19).replace('T', ' ')}`, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
        },
    });
    
    const data = await response.json();
    if (data.status === 'S') {
        console.log('Feedback:', data.data.feedback);
        console.log('Booking:', data.data.booking);
    }
}
```

**使用Laravel HTTP Client**:
```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)
    ->get('http://localhost:8000/api/feedbacks/123/booking-details', [
        'timestamp' => now()->format('Y-m-d H:i:s'),
    ]);

$data = $response->json();
if ($data['status'] === 'S') {
    $feedback = $data['data']['feedback'];
    $booking = $data['data']['booking'];
}
```

---

## 📊 响应格式

### 成功响应 (Status: 200)

```json
{
    "status": "S",
    "message": "Booking details retrieved successfully",
    "data": {
        "feedback": {
            "id": 123,
            "subject": "Great facility!",
            "type": "compliment",
            "rating": 5,
            "status": "resolved",
            "created_at": "2024-01-15 10:30:00"
        },
        "booking": {
            "id": 456,
            "user_id": 789,
            "user_name": "John Doe",
            "user_email": "john@example.com",
            "facility_id": 10,
            "facility_name": "Basketball Court A",
            "facility_code": "BCA001",
            "booking_date": "2024-01-20",
            "start_time": "2024-01-20 10:00:00",
            "end_time": "2024-01-20 12:00:00",
            "duration_hours": 2.0,
            "purpose": "Basketball practice",
            "status": "completed",
            "expected_attendees": 10,
            "created_at": "2024-01-15 08:15:00"
        },
        "attendees_count": 8,
        "slots_count": 1
    },
    "timestamp": "2024-01-15 14:30:05"
}
```

### 错误响应示例

**1. Feedback不关联Booking (Status: 404)**:
```json
{
    "status": "F",
    "message": "This feedback is not related to a booking",
    "data": {
        "feedback": {
            "id": 123,
            "subject": "General feedback",
            "type": "general"
        }
    },
    "timestamp": "2024-01-15 14:30:00"
}
```

**2. 权限不足 (Status: 403)**:
```json
{
    "status": "F",
    "message": "Unauthorized. You can only view booking details for your own feedbacks.",
    "timestamp": "2024-01-15 14:30:00"
}
```

**3. 缺少timestamp (Status: 422)**:
```json
{
    "status": "F",
    "message": "Validation error: timestamp or requestID is mandatory",
    "errors": {
        "timestamp": "Either timestamp or requestID must be provided"
    },
    "timestamp": "2024-01-15 14:30:00"
}
```

**4. Web Service失败 (Status: 500)**:
```json
{
    "status": "E",
    "message": "Failed to retrieve booking details",
    "error": "Connection timeout",
    "timestamp": "2024-01-15 14:30:00"
}
```

---

## 🔄 工作流程

```
1. 用户请求 GET /api/feedbacks/{id}/booking-details
   ↓
2. 验证timestamp/requestID (IFA标准)
   ↓
3. 检查用户权限（只能查看自己的feedbacks，或admin可以查看所有）
   ↓
4. 检查feedback是否存在
   ↓
5. 检查feedback是否关联到booking (booking_id不为空)
   ↓
6. 调用Booking Module的web service
   POST /api/bookings/service/get-info
   ↓
7. 如果成功 → 返回booking详情
   如果失败 → Fallback到直接数据库查询
   ↓
8. 返回响应（包含feedback和booking信息）
```

---

## ✅ 特性

1. **IFA标准合规**: 所有请求和响应都符合IFA标准
2. **权限控制**: 用户只能查看自己的feedbacks的booking详情
3. **错误处理**: 完善的错误处理和日志记录
4. **Fallback机制**: Web service失败时自动fallback到直接查询
5. **性能优化**: 10秒超时设置，避免长时间等待
6. **日志记录**: 记录所有重要操作和错误

---

## 🧪 测试建议

### 测试场景

1. **正常场景**:
   - Feedback有关联的booking_id
   - 用户有权限查看
   - Web service正常工作

2. **无关联Booking**:
   - Feedback没有booking_id
   - 应该返回404错误

3. **权限不足**:
   - 用户尝试查看他人的feedback
   - 应该返回403错误

4. **Web Service失败**:
   - Booking Module不可用
   - 应该fallback到直接查询

5. **缺少timestamp**:
   - 请求不包含timestamp或requestID
   - 应该返回422验证错误

---

## 📝 使用场景

1. **前端显示Feedback详情时**:
   - 用户查看feedback时，可以点击查看关联的booking详情
   - 显示完整的booking信息（facility、时间、状态等）

2. **管理员审核Feedback时**:
   - 管理员查看feedback时，可以查看相关booking信息
   - 帮助理解feedback的上下文

3. **报告生成**:
   - 生成feedback报告时，可以包含相关booking信息
   - 分析booking与feedback的关系

---

## 🎯 总结

✅ **已实现**:
- Feedback模块可以使用Booking Web Service
- 符合IFA标准
- 包含完善的错误处理和fallback机制
- 路由已正确配置

✅ **优势**:
- 模块间解耦（Feedback模块通过web service获取Booking信息）
- 符合微服务架构理念
- 易于维护和扩展

---

**实现日期**: 2024-01-15  
**实现文件**: 
- `app/Http/Controllers/API/FeedbackController.php`
- `routes/api.php`

