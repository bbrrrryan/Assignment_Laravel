# TARUMT Facilities Management System - Development Guide

## Table of Contents
1. [System Overview](#system-overview)
2. [Architecture & Design Patterns](#architecture--design-patterns)
3. [Database Design](#database-design)
4. [REST API Architecture](#rest-api-architecture)
5. [Module Implementation](#module-implementation)
6. [Authentication & Authorization](#authentication--authorization)
7. [Best Practices & Theory](#best-practices--theory)
8. [API Documentation](#api-documentation)

---

## System Overview

The TARUMT Facilities Management System is a comprehensive web-based platform built with Laravel that manages campus facilities, bookings, user interactions, and administrative operations. The system follows RESTful API principles and implements modern software engineering practices.

### Key Features
- **User Management**: Role-based access control, user activity tracking, CSV bulk upload
- **Notification System**: Multi-channel notifications with scheduling and targeting
- **Loyalty Program**: Points-based reward system with certificates
- **Feedback Management**: User feedback collection and moderation
- **Facility Management**: Facility CRUD operations with availability tracking
- **Booking System**: Advanced booking with conflict detection and approval workflow

---

## Architecture & Design Patterns

### 1. MVC (Model-View-Controller) Architecture

Laravel follows the MVC pattern, which separates concerns:

```
┌─────────────┐
│   Routes    │ → Defines API endpoints
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Controllers │ → Business logic & request handling
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Models    │ → Database interactions & relationships
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Database   │ → Data persistence
└─────────────┘
```

**Benefits:**
- **Separation of Concerns**: Each component has a single responsibility
- **Maintainability**: Changes in one layer don't affect others
- **Testability**: Each component can be tested independently
- **Scalability**: Easy to extend and modify

### 2. RESTful API Design

REST (Representational State Transfer) principles:

#### HTTP Methods
- **GET**: Retrieve resources (read-only)
- **POST**: Create new resources
- **PUT/PATCH**: Update existing resources
- **DELETE**: Remove resources

#### Resource Naming Convention
```
GET    /api/users              → List all users
GET    /api/users/{id}          → Get specific user
POST   /api/users              → Create user
PUT    /api/users/{id}          → Update user
DELETE /api/users/{id}          → Delete user
```

#### Status Codes
- `200 OK`: Successful GET, PUT, PATCH
- `201 Created`: Successful POST
- `204 No Content`: Successful DELETE
- `400 Bad Request`: Invalid request data
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource doesn't exist
- `422 Unprocessable Entity`: Validation errors
- `500 Internal Server Error`: Server error

### 3. Eloquent ORM (Object-Relational Mapping)

Eloquent provides an ActiveRecord implementation:

```php
// Instead of raw SQL:
// SELECT * FROM users WHERE id = 1;
$user = User::find(1);

// Relationships are handled elegantly:
$user->bookings()->where('status', 'approved')->get();
```

**Advantages:**
- **Type Safety**: PHP type hints and IDE support
- **Relationship Management**: Automatic handling of foreign keys
- **Query Builder**: Fluent, chainable query methods
- **Model Events**: Hooks for create, update, delete operations

### 4. Repository Pattern (Implicit)

While not explicitly implemented, Laravel's structure encourages repository-like patterns:

```php
// Controller uses Model directly (simplified approach)
$users = User::with('role')->paginate(15);

// Could be abstracted to Repository for complex queries:
// $users = $userRepository->getUsersWithRoles();
```

---

## Database Design

### Entity Relationship Diagram (ERD) Overview

```
Users ──┬── UserActivityLogs
        ├── Notifications (many-to-many)
        ├── LoyaltyPoints
        ├── Certificates
        ├── Rewards (many-to-many)
        ├── Feedbacks
        └── Bookings

Roles ──┬── Users
        └── Permissions (many-to-many)

Facilities ──┬── Bookings
             └── Feedbacks

Bookings ──┬── BookingStatusHistory
           └── Feedbacks

Notifications ── UserNotifications (pivot)
Rewards ── UserRewards (pivot)
```

### Key Design Principles

#### 1. Normalization
- **1NF**: Each column contains atomic values
- **2NF**: No partial dependencies (all non-key attributes depend on full primary key)
- **3NF**: No transitive dependencies

**Example:**
```sql
-- Instead of storing role name in users table:
users: id, name, email, role_name  ❌

-- We use foreign key:
users: id, name, email, role_id     ✅
roles: id, name, permissions        ✅
```

#### 2. Foreign Keys & Constraints
```php
// Migration example
$table->foreignId('user_id')
    ->constrained()
    ->onDelete('cascade');  // Delete bookings when user is deleted
```

#### 3. Indexes for Performance
```php
$table->index('email');           // Fast lookups
$table->index(['status', 'date']); // Composite index for filtered queries
```

#### 4. Pivot Tables (Many-to-Many)
```php
// user_notification pivot table
user_id | notification_id | is_read | read_at | acknowledged_at
```

---

## REST API Architecture

### Request/Response Flow

```
Client Request
    │
    ▼
┌─────────────────┐
│  Middleware     │ → Authentication, CORS, Rate Limiting
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Route Handler  │ → Matches URL to controller method
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Controller     │ → Validates input, calls business logic
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Model/Service  │ → Database operations, business rules
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Database       │ → Data persistence
└────────┬────────┘
         │
         ▼
JSON Response → Client
```

### API Response Structure

**Success Response:**
```json
{
    "message": "Resource retrieved successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

**Error Response:**
```json
{
    "message": "Validation error",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 6 characters."]
    }
}
```

**Paginated Response:**
```json
{
    "message": "Users retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [...],
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

---

## Module Implementation

### 1. User Management Module

#### Features
- CRUD operations for users
- Role assignment and permission management
- Activity logging
- CSV bulk upload
- Profile management

#### Key Implementation Details

**Activity Logging:**
```php
// Automatic logging on user actions
auth()->user()->activityLogs()->create([
    'action' => 'create_user',
    'description' => "Created user: {$user->name}",
    'metadata' => ['user_id' => $user->id],
]);
```

**CSV Upload:**
- Validates CSV structure
- Processes rows in transaction
- Returns detailed success/failure report
- Handles validation errors gracefully

**Theory:**
- **Audit Trail**: Activity logs provide accountability
- **Bulk Operations**: Transaction ensures data integrity (all-or-nothing)
- **Validation**: Input validation prevents invalid data entry

### 2. Notification Management Module

#### Features
- Create and send notifications
- Target specific user groups
- Scheduled notifications
- Read/acknowledgment tracking

#### Key Implementation Details

**Target Audience Logic:**
```php
private function getTargetUsers(Notification $notification): array
{
    switch ($notification->target_audience) {
        case 'all':
            return User::where('status', 'active')->pluck('id')->toArray();
        case 'students':
            return User::whereHas('role', fn($q) => $q->where('name', 'student'))
                ->pluck('id')->toArray();
        // ... other cases
    }
}
```

**Pivot Table Usage:**
```php
// Many-to-many relationship with additional pivot data
$notification->users()->sync([
    $userId => [
        'is_read' => false,
        'is_acknowledged' => false,
    ]
]);
```

**Theory:**
- **Observer Pattern**: Notifications can trigger events
- **Strategy Pattern**: Different targeting strategies (all, students, specific)
- **Pivot Tables**: Store relationship metadata (read status, timestamps)

### 3. Loyalty Management Module

#### Features
- Points awarding and tracking
- Reward redemption
- Certificate issuance
- Points history

#### Key Implementation Details

**Points Transaction:**
```php
// Award points (positive)
$user->loyaltyPoints()->create([
    'points' => 100,
    'action_type' => 'facility_booking',
]);

// Redeem points (negative)
$user->loyaltyPoints()->create([
    'points' => -50,
    'action_type' => 'reward_redemption',
]);
```

**Reward Redemption with Transaction:**
```php
DB::beginTransaction();
try {
    // Deduct points
    $user->loyaltyPoints()->create(['points' => -$reward->points_required]);
    
    // Attach reward
    $user->rewards()->attach($reward->id, ['status' => 'pending']);
    
    // Update stock
    $reward->decrement('stock_quantity');
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}
```

**Theory:**
- **ACID Properties**: Transactions ensure atomicity
- **Double-Entry Bookkeeping**: Points tracked as transactions (positive/negative)
- **Stock Management**: Prevents over-redemption

### 4. Feedback Management Module

#### Features
- Submit feedback/complaints
- Admin response system
- Content moderation (blocking)
- Rating system

#### Key Implementation Details

**Feedback Types:**
- Complaint
- Suggestion
- Compliment
- General

**Moderation Workflow:**
```php
// Admin can block inappropriate feedback
$feedback->update([
    'is_blocked' => true,
    'block_reason' => $request->reason,
    'status' => 'blocked',
]);
```

**Theory:**
- **Content Moderation**: Protects system integrity
- **Response System**: Two-way communication channel
- **Rating System**: Quantitative feedback collection

### 5. Facility Management Module

#### Features
- Facility CRUD operations
- Availability checking
- Utilization statistics
- Maintenance scheduling

#### Key Implementation Details

**Availability Check:**
```php
// Check for time conflicts
$conflicts = Booking::where('facility_id', $facilityId)
    ->whereDate('booking_date', $date)
    ->where(function($query) use ($startTime, $endTime) {
        // Check time overlap logic
    })
    ->exists();
```

**Utilization Calculation:**
```php
$utilizationPercentage = ($totalHours / $maxPossibleHours) * 100;
```

**Theory:**
- **Conflict Detection**: Prevents double-booking
- **Time Overlap Algorithm**: Checks if time ranges intersect
- **Analytics**: Utilization metrics for decision-making

### 6. Booking & Scheduling Module

#### Features
- Create bookings with conflict detection
- Approval workflow
- Cancellation handling
- Status history tracking

#### Key Implementation Details

**Conflict Detection Algorithm:**
```php
// Two time ranges overlap if:
// start1 < end2 AND end1 > start2

$overlaps = ($startTime < $bookingEnd && $endTime > $bookingStart);
```

**Status History:**
```php
// Track all status changes
$booking->statusHistory()->create([
    'status' => 'approved',
    'changed_by' => auth()->id(),
    'notes' => 'Booking approved by admin',
]);
```

**Approval Workflow:**
```
pending → approved/rejected → completed/cancelled
```

**Theory:**
- **State Machine**: Booking status follows defined transitions
- **Audit Trail**: Status history provides accountability
- **Conflict Resolution**: Prevents resource conflicts

---

## Authentication & Authorization

### Laravel Sanctum

Sanctum provides token-based authentication for SPAs and mobile applications.

#### Token Generation
```php
$token = $user->createToken('auth_token')->plainTextToken;
```

#### Token Usage
```http
Authorization: Bearer {token}
```

#### Middleware Protection
```php
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});
```

### Role-Based Access Control (RBAC)

**Implementation:**
```php
// Check permissions
if (!$user->hasPermission('manage_users')) {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

**Role Hierarchy:**
- **Admin**: Full system access
- **Staff**: Limited administrative access
- **Student**: User-level access

**Theory:**
- **Principle of Least Privilege**: Users get minimum required permissions
- **Separation of Duties**: Different roles for different responsibilities
- **Token-Based Auth**: Stateless, scalable authentication

---

## Best Practices & Theory

### 1. Validation

**Why Validate?**
- **Data Integrity**: Ensures data meets business rules
- **Security**: Prevents injection attacks
- **User Experience**: Provides clear error messages

**Laravel Validation:**
```php
$request->validate([
    'email' => 'required|email|unique:users',
    'password' => 'required|min:6|confirmed',
]);
```

### 2. Error Handling

**Consistent Error Responses:**
```php
try {
    // Operation
} catch (\Exception $e) {
    return response()->json([
        'message' => 'Operation failed',
        'error' => $e->getMessage(),
    ], 500);
}
```

### 3. Database Transactions

**Why Use Transactions?**
- **Atomicity**: All operations succeed or all fail
- **Consistency**: Database remains in valid state
- **Data Integrity**: Prevents partial updates

**Example:**
```php
DB::beginTransaction();
try {
    // Multiple related operations
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}
```

### 4. Eager Loading

**N+1 Problem:**
```php
// Bad: N+1 queries
$users = User::all();
foreach ($users as $user) {
    echo $user->role->name; // Query for each user
}

// Good: Eager loading
$users = User::with('role')->get(); // Single query with join
```

### 5. Pagination

**Why Paginate?**
- **Performance**: Limits data transfer
- **User Experience**: Faster page loads
- **Memory Efficiency**: Reduces server memory usage

```php
$users = User::paginate(15); // 15 items per page
```

### 6. Soft Deletes

**Concept:**
- Records are marked as deleted, not physically removed
- Can be restored if needed
- Maintains referential integrity

```php
// Model
use SoftDeletes;

// Query excludes deleted records automatically
User::all(); // Only active users
```

### 7. Model Relationships

**Types:**
- **One-to-One**: `hasOne()` / `belongsTo()`
- **One-to-Many**: `hasMany()` / `belongsTo()`
- **Many-to-Many**: `belongsToMany()`

**Benefits:**
- **Code Reusability**: Define once, use everywhere
- **Type Safety**: IDE autocomplete support
- **Query Optimization**: Eager loading prevents N+1

---

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication
All protected endpoints require Bearer token:
```
Authorization: Bearer {your_token_here}
```

### Endpoints Summary

#### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user
- `GET /api/me` - Get authenticated user

#### User Management
- `GET /api/users` - List users (with filters)
- `POST /api/users` - Create user
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user
- `GET /api/users/{id}/activity-logs` - Get user activity
- `POST /api/users/upload-csv` - Bulk upload users
- `PUT /api/users/profile/update` - Update own profile

#### Notifications
- `GET /api/notifications` - List notifications
- `POST /api/notifications` - Create notification
- `GET /api/notifications/{id}` - Get notification
- `PUT /api/notifications/{id}` - Update notification
- `DELETE /api/notifications/{id}` - Delete notification
- `POST /api/notifications/{id}/send` - Send notification
- `GET /api/notifications/user/my-notifications` - Get my notifications
- `PUT /api/notifications/{id}/read` - Mark as read
- `PUT /api/notifications/{id}/acknowledge` - Acknowledge

#### Loyalty
- `GET /api/loyalty/points` - Get user points
- `GET /api/loyalty/points/history` - Points history
- `POST /api/loyalty/points/award` - Award points (admin)
- `GET /api/loyalty/rewards` - List rewards
- `POST /api/loyalty/rewards/redeem` - Redeem reward
- `GET /api/loyalty/certificates` - Get certificates
- `POST /api/loyalty/certificates/issue` - Issue certificate (admin)

#### Feedback
- `GET /api/feedbacks` - List feedbacks
- `POST /api/feedbacks` - Submit feedback
- `GET /api/feedbacks/{id}` - Get feedback
- `PUT /api/feedbacks/{id}` - Update feedback
- `DELETE /api/feedbacks/{id}` - Delete feedback
- `PUT /api/feedbacks/{id}/respond` - Admin response
- `PUT /api/feedbacks/{id}/block` - Block feedback

#### Facilities
- `GET /api/facilities` - List facilities
- `POST /api/facilities` - Create facility
- `GET /api/facilities/{id}` - Get facility
- `PUT /api/facilities/{id}` - Update facility
- `DELETE /api/facilities/{id}` - Delete facility
- `GET /api/facilities/{id}/availability` - Check availability
- `GET /api/facilities/{id}/utilization` - Get utilization stats

#### Bookings
- `GET /api/bookings` - List bookings
- `POST /api/bookings` - Create booking
- `GET /api/bookings/{id}` - Get booking
- `PUT /api/bookings/{id}` - Update booking
- `DELETE /api/bookings/{id}` - Delete booking
- `PUT /api/bookings/{id}/approve` - Approve booking
- `PUT /api/bookings/{id}/reject` - Reject booking
- `PUT /api/bookings/{id}/cancel` - Cancel booking
- `GET /api/bookings/user/my-bookings` - Get my bookings
- `GET /api/bookings/facility/{facilityId}/availability` - Check availability

---

## Testing the API

### Using Postman/Thunder Client

1. **Register/Login:**
```json
POST /api/login
{
    "email": "admin@example.com",
    "password": "password123"
}
```

2. **Copy the token from response**

3. **Set Authorization Header:**
```
Authorization: Bearer {token}
```

4. **Make API calls to protected endpoints**

### Example cURL Commands

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'

# Get Users (with token)
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer {token}"
```

---

## Conclusion

This system demonstrates:
- **RESTful API Design**: Following industry standards
- **Laravel Best Practices**: Using framework features effectively
- **Database Design**: Normalized, efficient schema
- **Security**: Authentication, authorization, validation
- **Scalability**: Pagination, eager loading, transactions
- **Maintainability**: Clean code, separation of concerns

The architecture supports future enhancements like:
- Real-time notifications (WebSockets)
- File uploads for certificates/rewards
- Advanced reporting and analytics
- Mobile app integration
- Email notifications
- Automated booking reminders

---

## References

- [Laravel Documentation](https://laravel.com/docs)
- [REST API Design](https://restfulapi.net/)
- [Database Normalization](https://www.studytonight.com/dbms/database-normalization.php)
- [Design Patterns](https://refactoring.guru/design-patterns)

---

**Developed by:**
- Liew Zi Li (User Management & Notification Management)
- Boo Kai Jie (Loyalty Management & Feedback Management)
- Ng Jhun Hou (Facility Management)
- Low Kim Hong (Booking & Scheduling)

**Framework:** Laravel 10.x
**Database:** MySQL
**Authentication:** Laravel Sanctum

