# Feedback提交表单添加"Related Booking"选项 - 实现说明

## ✅ 已完成的功能

现在在提交Feedback时，用户可以**选择关联的Booking**了！

---

## 📝 实现内容

### 1. 表单添加"Related Booking"选项

**文件**: `resources/views/feedbacks/index.blade.php`

在"Related Facility"下方添加了：
```html
<div class="form-group">
    <label>Related Booking (Optional)</label>
    <select id="feedbackBooking">
        <option value="">None</option>
    </select>
    <small>Select a booking if this feedback is related to a specific booking</small>
</div>
```

### 2. 后端接受booking_id参数

**文件**: `app/Http/Controllers/API/FeedbackController.php`

- ✅ 添加了`booking_id`验证
- ✅ 验证booking是否属于当前用户（安全验证）
- ✅ 传递给FeedbackFactory

### 3. FeedbackFactory支持booking_id

**文件**: `app/Factories/FeedbackFactory.php`

- ✅ `makeFeedback()`方法现在接受`$bookingId`参数
- ✅ 创建feedback时保存`booking_id`

### 4. Feedback模型更新

**文件**: `app/Models/Feedback.php`

- ✅ 添加`booking_id`到`$fillable`数组
- ✅ 添加`booking()`关系方法

### 5. 前端加载Bookings

**文件**: `public/js/feedbacks/index.js`

- ✅ 添加了`loadBookings()`函数
- ✅ 自动加载用户的所有bookings
- ✅ 在下拉框中显示格式：`Booking #123 - Facility Name - Date (status)`
- ✅ 提交时包含`booking_id`

---

## 🎯 使用流程

### 用户操作流程

1. **打开Submit Feedback表单**
   - 点击"Submit Feedback"按钮

2. **填写表单**
   - Type: 选择类型（Complaint/Suggestion/Compliment/General）
   - Subject: 输入主题
   - Message: 输入内容
   - Rating: 选择评分
   - Image: 上传图片（可选）
   - **Related Facility**: 选择关联的facility（可选）
   - **Related Booking**: 选择关联的booking（可选）⭐ **新增**

3. **提交Feedback**
   - 点击"Submit"按钮
   - 如果选择了booking，feedback会关联到该booking

4. **查看Feedback详情**
   - 进入Feedback详情页面
   - 如果有关联的booking，会显示"Related Booking"部分
   - 点击"View Booking Details"查看完整booking信息（通过Web Service）

---

## 📊 Booking下拉框显示格式

下拉框中的选项格式：
```
Booking #123 - Basketball Court A - 1/20/2024 (approved)
Booking #124 - Library - 1/21/2024 (completed)
Booking #125 - Sports Hall - 1/22/2024 (pending)
```

包含信息：
- Booking ID
- Facility名称
- Booking日期
- Booking状态

---

## 🔒 安全验证

### 权限检查

```php
// 验证booking是否属于当前用户
if (isset($validated['booking_id'])) {
    $booking = \App\Models\Booking::find($validated['booking_id']);
    if ($booking && $booking->user_id !== auth()->id()) {
        return response()->json([
            'status' => 'F',
            'message' => 'You can only associate feedback with your own bookings',
        ], 403);
    }
}
```

**保护措施**:
- ✅ 用户只能选择自己的bookings
- ✅ 如果尝试关联他人的booking，会返回403错误

---

## 🧪 测试步骤

### 1. 测试提交Feedback时选择Booking

1. **登录系统**（学生或员工账号）
2. **进入Feedbacks页面**
3. **点击"Submit Feedback"按钮**
4. **填写表单**:
   - 选择Type
   - 输入Subject和Message
   - 选择Rating
   - **在"Related Booking"下拉框中选择一个booking** ⭐
5. **提交Feedback**
6. **验证**: 检查feedback是否成功关联到选择的booking

### 2. 测试查看Booking详情

1. **进入刚才提交的Feedback详情页面**
2. **应该看到"Related Booking"部分**
3. **点击"View Booking Details"按钮**
4. **验证**: 应该显示完整的booking信息（通过Web Service获取）

### 3. 测试权限验证

1. **尝试通过API直接关联他人的booking**（应该失败）
2. **验证**: 返回403错误

---

## 📝 代码变更总结

### 修改的文件

1. ✅ `app/Http/Controllers/API/FeedbackController.php`
   - 添加`booking_id`验证
   - 添加权限检查

2. ✅ `app/Factories/FeedbackFactory.php`
   - `makeFeedback()`方法添加`$bookingId`参数

3. ✅ `app/Models/Feedback.php`
   - 添加`booking_id`到`$fillable`
   - 添加`booking()`关系方法

4. ✅ `resources/views/feedbacks/index.blade.php`
   - 添加"Related Booking"表单字段

5. ✅ `public/js/feedbacks/index.js`
   - 添加`loadBookings()`函数
   - 提交时包含`booking_id`

---

## 🎉 功能完成

现在用户可以：
1. ✅ **提交Feedback时选择关联的Booking**
2. ✅ **在Feedback详情页面查看Booking详情**（通过Web Service）
3. ✅ **系统自动验证权限**（只能关联自己的bookings）

---

**实现日期**: 2024-01-15  
**功能状态**: ✅ 已完成并可用

