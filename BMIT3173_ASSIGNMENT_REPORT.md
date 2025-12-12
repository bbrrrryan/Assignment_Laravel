# BMIT3173 Integrative Programming
## ASSIGNMENT 202509

**Student Name**: Liew Zi Li  
**Student ID**: [Your Student ID]  
**Programme**: [Your Programme]  
**Tutorial Group**: [Your Tutorial Group]  
**System Title**: TARUMT Facilities Management System  
**Modules**: User Management Module, Notification Management Module

---

## Declaration

I confirm that I have read and complied with all the terms and conditions of Tunku Abdul Rahman University of Management and Technology's plagiarism policy.

I declare that this assignment is free from all forms of plagiarism and for all intents and purposes is my own properly derived work.

---

## Plagiarism Statement Form

I, **LIEW ZI LI** (Block Capitals) **Student ID: [Your Student ID]** **Programme: [Your Programme]** **Tutorial Group: [Your Tutorial Group]** confirm that the submitted work are all my own work and is in my own words.

I (Liew Zi Li) acknowledge the use of AI generative technology.

**Signature**: ________________  
**Date**: ________________

---

## Table of Contents

1. [Introduction to the System](#1-introduction-to-the-system)
2. [Module Description](#2-module-description)
3. [Entity Classes](#3-entity-classes)
4. [Design Pattern](#4-design-pattern)
5. [Software Security](#5-software-security)
6. [Web Services](#6-web-services)
7. [Index](#7-index)
8. [References](#8-references)

---

## 1. Introduction to the System

The TARUMT Facilities Management System is a comprehensive web-based platform designed to manage campus facilities, user interactions, bookings, feedback, loyalty programs, and notifications. The system is built using Laravel 10 framework with PHP and MySQL database, following RESTful API architecture principles.

The system provides role-based access control with three main user roles: **Admin**, **Staff**, and **Student**. Administrators and staff have access to the admin dashboard for managing users, facilities, and notifications, while students can browse facilities, make bookings, view notifications, and participate in loyalty programs.

Key features of the system include:
- **User Management**: Complete user lifecycle management with role-based permissions
- **Notification System**: Global and targeted notification delivery with read/unread tracking
- **Facility Management**: Facility booking system with conflict detection
- **Security**: Token-based authentication, input validation, and secure password handling
- **Web Services**: RESTful API for module integration

---

## 2. Module Description

I am responsible for two main modules: **User Management Module** and **Notification Management Module**. Each module contains multiple functions as described below.

### 2.1 User Management Module

#### 2.1.1 User List View
**Function**: Display all users in the system with filtering and search capabilities.

**Screenshot Path**: `resources/views/admin/users/index.blade.php`

**Description**: 
- Shows a paginated list of all users
- Supports filtering by status (active/inactive) and role (admin/staff/student)
- Search functionality by name or email
- Export to CSV functionality
- CSV bulk import functionality
- Only Read and Update operations are allowed (no Create/Delete for security)

**Class Path**: `app/Http/Controllers/Admin/UserCRUDManagementController.php` → `index()` method

#### 2.1.2 User Details View
**Function**: Display detailed information about a specific user.

**Screenshot Path**: `resources/views/admin/users/show.blade.php`

**Description**:
- Shows complete user profile information
- Displays user activity logs
- Shows user's bookings, feedbacks, and notifications history

**Class Path**: `app/Http/Controllers/Admin/UserCRUDManagementController.php` → `show()` method

#### 2.1.3 User Edit Function
**Function**: Update user information with role-based restrictions.

**Screenshot Path**: `resources/views/admin/users/edit.blade.php`

**Description**:
- Admins can edit all fields including role and status
- Staff can only edit basic information (name, email, phone, address)
- Staff cannot change user role or set status to inactive
- Password can be updated with confirmation

**Class Path**: `app/Http/Controllers/Admin/UserCRUDManagementController.php` → `edit()` and `update()` methods

#### 2.1.4 CSV Export Function
**Function**: Export all users to CSV file for backup or external processing.

**Screenshot Path**: `resources/views/admin/users/index.blade.php` (Export CSV button)

**Description**:
- Exports user data to CSV format
- Includes: name, email, role, phone_number, address, status
- Password field is intentionally left empty for security

**Class Path**: `app/Http/Controllers/Admin/UserCRUDManagementController.php` → `exportCsv()` method

#### 2.1.5 CSV Import Function
**Function**: Bulk import users from CSV file.

**Screenshot Path**: `resources/views/admin/users/index.blade.php` (CSV Upload form)

**Description**:
- Upload CSV file to create multiple users at once
- Validates email format and uniqueness
- If password is empty, uses phone_number as password
- Uses database transactions to ensure data integrity
- Returns detailed success/error report

**Class Path**: `app/Http/Controllers/Admin/UserCRUDManagementController.php` → `uploadCsv()` method

### 2.2 Notification Management Module

#### 2.2.1 Notification List View (Admin)
**Function**: Display all notifications in the system for admin management.

**Screenshot Path**: `resources/views/admin/notifications/index.blade.php`

**Description**:
- Shows all notifications with details (ID, Title, Type, Priority, Created By, Created At, Status)
- Table rows are clickable to view notification details
- Supports filtering and search
- Create new notification button for admins

**Class Path**: `app/Http/Controllers/Admin/NotificationController.php` → `index()` method

#### 2.2.2 Notification Details View (Admin)
**Function**: Display detailed information about a specific notification.

**Screenshot Path**: `resources/views/admin/notifications/show.blade.php`

**Description**:
- Shows complete notification information
- Displays notification message, type, priority, and status
- Edit and Delete buttons for admins

**Class Path**: `app/Http/Controllers/Admin/NotificationController.php` → `show()` method

#### 2.2.3 Notification Edit Function
**Function**: Update notification information.

**Screenshot Path**: `resources/views/admin/notifications/edit.blade.php`

**Description**:
- Edit notification title, message, type, priority, and active status
- All notifications are global (target_audience = 'all')

**Class Path**: `app/Http/Controllers/Admin/NotificationController.php` → `edit()` and `update()` methods

#### 2.2.4 User Notification List View
**Function**: Display notifications for the logged-in user.

**Screenshot Path**: `resources/views/notifications/index.blade.php`

**Description**:
- Shows all notifications assigned to the current user
- Visual distinction between read (grayed out) and unread notifications
- Entire row is clickable to view notification details
- Mark as Unread functionality for read notifications

**Class Path**: `app/Http/Controllers/API/NotificationController.php` → `myNotifications()` method

#### 2.2.5 Notification Details View (User)
**Function**: Display notification details and automatically mark as read.

**Screenshot Path**: `resources/views/notifications/show.blade.php`

**Description**:
- Shows notification title, message, type, and sender
- Automatically marks notification as read when viewed
- Back button to return to notification list

**Class Path**: `app/Http/Controllers/PageController.php` → `showNotification()` method

---

## 3. Entity Classes

### 3.1 Entity Class Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                         User                                 │
├─────────────────────────────────────────────────────────────┤
│ - id: int                                                    │
│ - name: string                                               │
│ - email: string                                              │
│ - password: string (hashed)                                  │
│ - role: string (admin|staff|student)                         │
│ - status: string (active|inactive)                           │
│ - phone_number: string|null                                  │
│ - address: string|null                                       │
│ - otp_code: string|null                                      │
│ - otp_expires_at: datetime|null                             │
│ - email_verified_at: datetime|null                           │
│ - last_login_at: datetime|null                              │
│ - created_at: datetime                                       │
│ - updated_at: datetime                                       │
├─────────────────────────────────────────────────────────────┤
│ + activityLogs(): HasMany<UserActivityLog>                  │
│ + notifications(): BelongsToMany<Notification>               │
│ + loyaltyPoints(): HasMany<LoyaltyPoint>                     │
│ + certificates(): HasMany<Certificate>                      │
│ + rewards(): BelongsToMany<Reward>                          │
│ + feedbacks(): HasMany<Feedback>                             │
│ + bookings(): HasMany<Booking>                               │
│ + isAdmin(): bool                                            │
│ + isStaff(): bool                                            │
│ + isStudent(): bool                                          │
│ + getTotalPointsAttribute(): int                             │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ (Many-to-Many)
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Notification                            │
├─────────────────────────────────────────────────────────────┤
│ - id: int                                                    │
│ - title: string                                              │
│ - message: string                                            │
│ - type: string (info|warning|success|error|reminder)        │
│ - priority: string|null (low|medium|high|urgent)            │
│ - created_by: int (Foreign Key → User.id)                    │
│ - target_audience: string (default: 'all')                  │
│ - target_user_ids: array|null                               │
│ - scheduled_at: datetime|null                               │
│ - expires_at: datetime|null                                 │
│ - is_active: bool                                           │
│ - created_at: datetime                                       │
│ - updated_at: datetime                                       │
├─────────────────────────────────────────────────────────────┤
│ + creator(): BelongsTo<User>                                 │
│ + users(): BelongsToMany<User>                               │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 Entity Class Implementation

#### User Entity Class
**File Path**: `app/Models/User.php`

```php
class User extends Authenticatable
{
    // Properties
    protected $fillable = [
        'name', 'email', 'password', 'role', 'status',
        'phone_number', 'address', 'last_login_at',
        'otp_code', 'otp_expires_at', 'email_verified_at'
    ];

    // Relationships using object references
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'user_notification')
                    ->withPivot('is_read', 'read_at', 
                              'is_acknowledged', 'acknowledged_at')
                    ->withTimestamps();
    }

    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class);
    }

    // Helper methods
    public function isAdmin()
    {
        $role = strtolower($this->role ?? '');
        return ($role === 'admin' || $role === 'administrator');
    }
}
```

#### Notification Entity Class
**File Path**: `app/Models/Notification.php`

```php
class Notification extends Model
{
    // Properties
    protected $fillable = [
        'title', 'message', 'type', 'priority',
        'created_by', 'target_audience', 'target_user_ids',
        'scheduled_at', 'expires_at', 'is_active'
    ];

    // Relationships using object references
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_notification')
                    ->withPivot('is_read', 'read_at',
                              'is_acknowledged', 'acknowledged_at')
                    ->withTimestamps();
    }
}
```

**Note**: The entity classes use object references (Eloquent relationships) instead of foreign keys to represent relationships between classes, as required.

---

## 4. Design Pattern

### 4.1 Description of Design Pattern

I have implemented the **Factory Pattern** (specifically, the **Simple Factory Pattern**) in the User Management Module.

The Factory Pattern is a creational design pattern that provides an interface for creating objects without specifying their exact classes. In this implementation, the `UserFactory` class centralizes the creation logic for different types of users (admin, staff, student) based on a role parameter.

**Why Factory Pattern?**
- **Encapsulation**: Hides the complex object creation logic from the client code
- **Flexibility**: Easy to add new user types without modifying existing code
- **Consistency**: Ensures all users are created with proper validation and default values
- **Maintainability**: Centralizes user creation logic, making it easier to modify in the future

### 4.2 Implementation of Design Pattern

#### Class Diagram

```
┌─────────────────────────────────────────┐
│          UserFactory                     │
├─────────────────────────────────────────┤
│ + makeUser(type: string,                │
│            name: string,                │
│            email: string,                │
│            password: string): User      │
└─────────────────────────────────────────┘
                    │
                    │ creates
                    ▼
┌─────────────────────────────────────────┐
│            User                         │
├─────────────────────────────────────────┤
│ - name: string                          │
│ - email: string                         │
│ - password: string (hashed)             │
│ - role: string                          │
│ - status: string                        │
└─────────────────────────────────────────┘
```

#### Implementation Code

**File Path**: `app/Factories/UserFactory.php`

```php
<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserFactory
{
    /**
     * Create a user with role string
     * 
     * @param string $type Role name ('admin', 'student', 'staff')
     * @param string $name
     * @param string $email
     * @param string $password
     * @return User
     */
    public static function makeUser($type, $name, $email, $password)
    {
        // Normalize role name
        $role = strtolower(trim($type));
        
        // Validate role - using simple if-else
        if ($role === 'admin' || $role === 'administrator') {
            $roleName = 'admin';
        } elseif ($role === 'student') {
            $roleName = 'student';
        } elseif ($role === 'staff') {
            $roleName = 'staff';
        } else {
            // Default to student if invalid
            $roleName = 'student';
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password), 
            'role' => $roleName,
            'status' => 'active' 
        ]);
    }
}
```

#### Usage Example

**File Path**: `app/Http/Controllers/PageController.php`

```php
use App\Factories\UserFactory;

// Create user using Factory Pattern
$user = UserFactory::makeUser(
    $request->role,      // 'admin', 'staff', or 'student'
    $request->name,
    $request->email,
    $request->password
);
```

#### Justification for Factory Pattern Choice

1. **Role-Based User Creation**: The system requires creating users with different roles (admin, staff, student). The Factory Pattern encapsulates this logic, ensuring consistent role validation and default values.

2. **Password Security**: The factory automatically hashes passwords using `Hash::make()`, ensuring security is not overlooked during user creation.

3. **Default Values**: The factory sets default values (e.g., `status = 'active'`) consistently for all users.

4. **Future Extensibility**: If new user types or roles are added in the future, only the factory method needs to be modified, not all the places where users are created.

5. **Code Reusability**: The factory method can be called from multiple places (registration, CSV import, admin creation) without duplicating creation logic.

---

## 5. Software Security

### 5.1 Potential Threat/Attack

#### Threat 1: SQL Injection Attack

**Description**: 
SQL Injection is a code injection technique where malicious SQL statements are inserted into an application's database query. Attackers can manipulate input fields to execute unauthorized SQL commands, potentially accessing, modifying, or deleting sensitive data.

**Attack Scenario**:
- An attacker enters malicious SQL code in a search field: `'; DROP TABLE users; --`
- If the application directly concatenates user input into SQL queries, the database could be compromised
- Attackers could extract sensitive information like passwords, personal data, or gain unauthorized access

**Impact**:
- Unauthorized data access
- Data loss or corruption
- Complete system compromise
- Privacy violations

#### Threat 2: Unauthorized Access to Admin Functions

**Description**:
Unauthorized access occurs when users without proper permissions attempt to access or modify admin-only functions. This includes students trying to access admin dashboard, modify user roles, or perform administrative actions.

**Attack Scenario**:
- A student user directly accesses admin URLs like `/admin/users` or `/admin/dashboard`
- Attempts to modify user roles or status through API endpoints
- Tries to bypass frontend restrictions by making direct API calls

**Impact**:
- Unauthorized privilege escalation
- Data manipulation by unauthorized users
- System integrity compromise
- Violation of access control policies

### 5.2 Secure Coding Practice

#### Secure Practice 1: Parameterized Queries (Preventing SQL Injection)

**Implementation**: 
Instead of using raw SQL queries with string concatenation, the system uses Laravel's Eloquent ORM, which automatically uses parameterized queries (prepared statements) to prevent SQL injection.

**Code Location**: `app/Http/Controllers/Admin/UserCRUDManagementController.php`

**Implementation**:

```php
// ❌ VULNERABLE CODE (NOT USED):
// $users = DB::select("SELECT * FROM users WHERE name = '" . $search . "'");

// ✅ SECURE CODE (USED):
public function index(Request $request)
{
    $query = User::query();
    
    if ($request->has('search') && $request->search) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
    
    $users = $query->paginate(15);
    return view('admin.users.index', compact('users'));
}
```

**How It Works**:
- Eloquent ORM automatically escapes and parameterizes all input values
- The `where()` method uses prepared statements internally
- User input is never directly concatenated into SQL queries
- Special characters are automatically escaped

**Screenshot Path**: `app/Http/Controllers/Admin/UserCRUDManagementController.php` (Lines 18-48)

#### Secure Practice 2: Multi-Layer Authorization (Preventing Unauthorized Access)

**Implementation**: 
The system implements authorization at multiple layers: route middleware, controller base class, and method-level checks to ensure only authorized users can access admin functions.

**Code Location**: 
1. `app/Http/Middleware/AdminMiddleware.php`
2. `app/Http/Controllers/Admin/AdminBaseController.php`
3. `app/Http/Controllers/Admin/UserCRUDManagementController.php`

**Layer 1 - Route Middleware**:

**File**: `routes/web.php`
```php
Route::prefix('admin')->middleware('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::resource('users', UserCRUDManagementController::class);
});
```

**Layer 2 - Middleware Implementation**:

**File**: `app/Http/Middleware/AdminMiddleware.php`
```php
public function handle(Request $request, Closure $next): Response
{
    if (!auth()->check()) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $user = auth()->user();

    // Block students from accessing admin routes
    if ($user->isStudent()) {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthorized. Admin or Staff access required.',
            ], 403);
        }
        return redirect()->route('home')
            ->with('error', 'You do not have permission to access this page.');
    }

    return $next($request);
}
```

**Layer 3 - Base Controller Protection**:

**File**: `app/Http/Controllers/Admin/AdminBaseController.php`
```php
public function __construct()
{
    $this->middleware('auth');
    $this->middleware(function ($request, $next) {
        $user = auth()->user();
        
        if ($user->isStudent()) {
            return redirect()->route('home')
                ->with('error', 'You do not have permission to access this page.');
        }
        
        return $next($request);
    });
}
```

**Layer 4 - Method-Level Checks**:

**File**: `app/Http/Controllers/Admin/UserCRUDManagementController.php`
```php
public function update(Request $request, string $id)
{
    $user = User::findOrFail($id);
    $currentUser = auth()->user();

    // Only admin can change role
    if ($request->has('role')) {
        if ($currentUser->isAdmin()) {
            $updateData['role'] = $request->role;
        } else {
            return redirect()->back()
                ->withErrors(['role' => 'Only admin can change user role']);
        }
    }
    
    // Only admin can set status to inactive
    if ($request->has('status') && $request->status === 'inactive') {
        if (!$currentUser->isAdmin()) {
            return redirect()->back()
                ->withErrors(['status' => 'Only admin can set user to inactive']);
        }
    }
}
```

**How It Works**:
- **Route Level**: All admin routes are protected by `admin` middleware
- **Middleware Level**: Checks authentication and role before allowing access
- **Controller Level**: Base controller adds additional protection layer
- **Method Level**: Individual methods check permissions for specific actions
- **Defense in Depth**: Multiple layers ensure unauthorized access is blocked even if one layer is bypassed

**Screenshot Paths**:
- `app/Http/Middleware/AdminMiddleware.php` (Lines 16-42)
- `app/Http/Controllers/Admin/AdminBaseController.php` (Lines 13-41)
- `app/Http/Controllers/Admin/UserCRUDManagementController.php` (Lines 97-125)

**Note**: Input validation is implemented throughout the system but is not counted as one of the security practices for the identified threats, as per requirements.

---

## 6. Web Services

### 6.1 Overview

The system implements RESTful API web services using JSON format for data exchange. The User Management and Notification Management modules expose web services for consumption by other modules (Facility Management, Booking Management, Loyalty Management, Feedback Management) and also consume services from other modules.

### 6.2 Service Exposure

#### Web Service 1: Get User Information by ID

**Interface Agreement (IFA)**

| Field | Value |
|-------|-------|
| **Webservice Mechanism** | RESTful API (JSON-based) |
| **Description** | Retrieves user information by user ID |
| **Protocol** | RESTFUL |
| **Function Description** | Retrieves detailed user information including profile, role, status, and activity logs by user ID |
| **Source Module** | User Management Module |
| **Target Module** | Facility Management, Booking Management, Loyalty Management, Feedback Management |
| **URL** | `http://localhost:8000/api/users/{id}` |
| **Function Name** | `getUserById` |
| **HTTP Method** | GET |

**Web Services Request Parameter**

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| id | Integer | Mandatory | Unique ID of the user | Positive integer |
| requestID | String | Mandatory | Unique request identifier | UUID format |
| timeStamp | String | Mandatory | Time when the request was made | YYYY-MM-DD HH:MM:SS |

**Web Services Response Parameter**

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| status | String | Mandatory | Status of the request | "S": Success, "F": Fail, "E": Error |
| message | String | Mandatory | Response message | Descriptive text |
| data | Object | Mandatory | User information object | Contains user details |
| data.id | Integer | Mandatory | User ID | Positive integer |
| data.name | String | Mandatory | User name | Alphabet and numbers |
| data.email | String | Mandatory | User email | Valid email format |
| data.role | String | Mandatory | User role | "admin", "staff", or "student" |
| data.status | String | Mandatory | User status | "active" or "inactive" |
| data.phone_number | String | Optional | Phone number | Alphanumeric |
| data.address | String | Optional | Address | Text |
| timeStamp | String | Mandatory | Time when the response was generated | YYYY-MM-DD HH:MM:SS |

**Implementation Code**

**File Path**: `app/Http/Controllers/API/UserController.php`

```php
/**
 * Display the specified user
 */
public function show(string $id)
{
    $user = User::with(['activityLogs', 'notifications', 'bookings'])
                ->findOrFail($id);

    return response()->json([
        'status' => 'S',
        'message' => 'User retrieved successfully',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'phone_number' => $user->phone_number,
            'address' => $user->address,
            'created_at' => $user->created_at,
        ],
        'timeStamp' => now()->format('Y-m-d H:i:s')
    ]);
}
```

**Route Definition**

**File Path**: `routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('users')->middleware('admin')->group(function () {
        Route::get('/{id}', [UserController::class, 'show']);
    });
});
```

**Request Example** (cURL):
```bash
curl -X GET "http://localhost:8000/api/users/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "requestID: 550e8400-e29b-41d4-a716-446655440000" \
  -H "timeStamp: 2025-12-13 10:30:00"
```

**Response Example**:
```json
{
    "status": "S",
    "message": "User retrieved successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "admin",
        "status": "active",
        "phone_number": "0123456789",
        "address": "123 Main Street",
        "created_at": "2025-01-01T00:00:00.000000Z"
    },
    "timeStamp": "2025-12-13 10:30:05"
}
```

#### Web Service 2: Get User's Notifications

**Interface Agreement (IFA)**

| Field | Value |
|-------|-------|
| **Webservice Mechanism** | RESTful API (JSON-based) |
| **Description** | Retrieves all notifications for the authenticated user |
| **Protocol** | RESTFUL |
| **Function Description** | Retrieves user's notifications with read/unread status, filtered by type and read status |
| **Source Module** | Notification Management Module |
| **Target Module** | Dashboard Module, User Interface Module |
| **URL** | `http://localhost:8000/api/notifications/user/my-notifications` |
| **Function Name** | `getMyNotifications` |
| **HTTP Method** | GET |

**Web Services Request Parameter**

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| is_read | Integer | Optional | Filter by read status | 0: Unread, 1: Read, null: All |
| type | String | Optional | Filter by notification type | "info", "warning", "success", "error", "reminder" |
| per_page | Integer | Optional | Number of results per page | Positive integer (default: 15) |
| requestID | String | Mandatory | Unique request identifier | UUID format |
| timeStamp | String | Mandatory | Time when the request was made | YYYY-MM-DD HH:MM:SS |

**Web Services Response Parameter**

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| status | String | Mandatory | Status of the request | "S": Success, "F": Fail, "E": Error |
| message | String | Mandatory | Response message | Descriptive text |
| data | Object | Mandatory | Pagination object | Contains notifications array |
| data.data | Array | Mandatory | Array of notification objects | List of notifications |
| data.data[].id | Integer | Mandatory | Notification ID | Positive integer |
| data.data[].title | String | Mandatory | Notification title | Text |
| data.data[].message | String | Mandatory | Notification message | Text |
| data.data[].type | String | Mandatory | Notification type | "info", "warning", "success", "error", "reminder" |
| data.data[].pivot.is_read | Boolean | Mandatory | Read status | true or false |
| data.data[].created_at | String | Mandatory | Creation timestamp | ISO 8601 format |
| timeStamp | String | Mandatory | Time when the response was generated | YYYY-MM-DD HH:MM:SS |

**Implementation Code**

**File Path**: `app/Http/Controllers/API/NotificationController.php`

```php
/**
 * Get current user's notifications
 */
public function myNotifications(Request $request)
{
    $user = auth()->user();

    $query = $user->notifications()
        ->with('creator')
        ->where('is_active', true)
        ->when($request->is_read !== null, function($q) use ($request) {
            $q->wherePivot('is_read', $request->is_read);
        })
        ->when($request->type, fn($q) => $q->where('type', $request->type));

    $notifications = $query->latest('pivot_created_at')
        ->paginate($request->get('per_page', 15));

    return response()->json([
        'status' => 'S',
        'message' => 'My notifications retrieved successfully',
        'data' => $notifications,
        'timeStamp' => now()->format('Y-m-d H:i:s')
    ]);
}
```

**Route Definition**

**File Path**: `routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::get('/user/my-notifications', [NotificationController::class, 'myNotifications']);
    });
});
```

**Request Example** (cURL):
```bash
curl -X GET "http://localhost:8000/api/notifications/user/my-notifications?is_read=0&type=info" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "requestID: 550e8400-e29b-41d4-a716-446655440001" \
  -H "timeStamp: 2025-12-13 10:35:00"
```

**Response Example**:
```json
{
    "status": "S",
    "message": "My notifications retrieved successfully",
    "data": {
        "data": [
            {
                "id": 1,
                "title": "System Maintenance",
                "message": "System will be under maintenance tonight",
                "type": "warning",
                "pivot": {
                    "is_read": false
                },
                "created_at": "2025-12-13T08:00:00.000000Z"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 1
    },
    "timeStamp": "2025-12-13 10:35:05"
}
```

### 6.3 Service Consumption

#### Consumed Service: Get Facility Information

**Description**: 
The Notification Management Module consumes the Facility Management Module's web service to retrieve facility information when sending facility-related notifications.

**Interface Agreement (IFA)**

| Field | Value |
|-------|-------|
| **Webservice Mechanism** | RESTful API (JSON-based) |
| **Description** | Retrieves facility information by facility ID |
| **Protocol** | RESTFUL |
| **Function Description** | Retrieves detailed facility information including name, location, capacity, and status |
| **Source Module** | Facility Management Module |
| **Target Module** | Notification Management Module |
| **URL** | `http://localhost:8000/api/facilities/{id}` |
| **Function Name** | `getFacilityById` |
| **HTTP Method** | GET |

**Web Services Request Parameter**

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| id | Integer | Mandatory | Unique ID of the facility | Positive integer |
| requestID | String | Mandatory | Unique request identifier | UUID format |
| timeStamp | String | Mandatory | Time when the request was made | YYYY-MM-DD HH:MM:SS |

**Web Services Response Parameter**

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| status | String | Mandatory | Status of the request | "S": Success, "F": Fail, "E": Error |
| message | String | Mandatory | Response message | Descriptive text |
| data | Object | Mandatory | Facility information object | Contains facility details |
| data.id | Integer | Mandatory | Facility ID | Positive integer |
| data.name | String | Mandatory | Facility name | Text |
| data.location | String | Mandatory | Facility location | Text |
| data.capacity | Integer | Mandatory | Facility capacity | Positive integer |
| data.status | String | Mandatory | Facility status | "available", "maintenance", "unavailable" |
| timeStamp | String | Mandatory | Time when the response was generated | YYYY-MM-DD HH:MM:SS |

**Consumption Implementation**

**File Path**: `app/Http/Controllers/API/NotificationController.php`

```php
use Illuminate\Support\Facades\Http;

/**
 * Send notification with facility context
 */
public function sendWithFacilityContext(string $notificationId, int $facilityId)
{
    // Consume Facility Management service
    $token = auth()->user()->currentAccessToken()->token;
    
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
        'requestID' => Str::uuid()->toString(),
        'timeStamp' => now()->format('Y-m-d H:i:s')
    ])->get("http://localhost:8000/api/facilities/{$facilityId}");
    
    if ($response->successful()) {
        $facility = $response->json()['data'];
        
        // Use facility information in notification
        $notification = Notification::findOrFail($notificationId);
        $notification->message .= "\n\nRelated Facility: {$facility['name']} ({$facility['location']})";
        $notification->save();
        
        return response()->json([
            'status' => 'S',
            'message' => 'Notification sent with facility context',
            'timeStamp' => now()->format('Y-m-d H:i:s')
        ]);
    }
    
    return response()->json([
        'status' => 'E',
        'message' => 'Failed to retrieve facility information',
        'timeStamp' => now()->format('Y-m-d H:i:s')
    ], 500);
}
```

**How Web Service Technology is Used**:

1. **Service Exposure**: 
   - User Management Module exposes RESTful APIs for other modules to retrieve user information
   - Notification Management Module exposes APIs for retrieving user notifications

2. **Service Consumption**:
   - Notification Management Module consumes Facility Management APIs to enrich notification content
   - Other modules consume User Management APIs to get user details for their operations

3. **Integration Benefits**:
   - **Loose Coupling**: Modules communicate through well-defined APIs, not direct database access
   - **Reusability**: User information service can be consumed by multiple modules
   - **Maintainability**: Changes in one module don't affect others as long as API contracts are maintained
   - **Scalability**: Services can be deployed independently

4. **Authentication**: 
   - All API calls use Laravel Sanctum token-based authentication
   - Bearer tokens are required in the Authorization header

5. **Error Handling**:
   - Consistent error responses with status codes
   - Proper HTTP status codes (200, 401, 403, 404, 500)

---

## 7. Index

### Figure Index

| Figure No. | Description | Source Path |
|------------|------------|-------------|
| Figure 1 | User List View | `resources/views/admin/users/index.blade.php` |
| Figure 2 | User Details View | `resources/views/admin/users/show.blade.php` |
| Figure 3 | User Edit Form | `resources/views/admin/users/edit.blade.php` |
| Figure 4 | CSV Export Button | `resources/views/admin/users/index.blade.php` (Line 45) |
| Figure 5 | CSV Upload Form | `resources/views/admin/users/index.blade.php` (Lines 50-70) |
| Figure 6 | Notification List View (Admin) | `resources/views/admin/notifications/index.blade.php` |
| Figure 7 | Notification Details View (Admin) | `resources/views/admin/notifications/show.blade.php` |
| Figure 8 | Notification Edit Form | `resources/views/admin/notifications/edit.blade.php` |
| Figure 9 | User Notification List View | `resources/views/notifications/index.blade.php` |
| Figure 10 | Notification Details View (User) | `resources/views/notifications/show.blade.php` |
| Figure 11 | Entity Class Diagram | Section 3.1 of this report |
| Figure 12 | Factory Pattern Class Diagram | Section 4.2 of this report |
| Figure 13 | SQL Injection Prevention Code | `app/Http/Controllers/Admin/UserCRUDManagementController.php` (Lines 18-48) |
| Figure 14 | AdminMiddleware Code | `app/Http/Middleware/AdminMiddleware.php` (Lines 16-42) |
| Figure 15 | AdminBaseController Code | `app/Http/Controllers/Admin/AdminBaseController.php` (Lines 13-41) |
| Figure 16 | User Update Authorization Code | `app/Http/Controllers/Admin/UserCRUDManagementController.php` (Lines 97-125) |
| Figure 17 | Get User API Implementation | `app/Http/Controllers/API/UserController.php` (show method) |
| Figure 18 | Get Notifications API Implementation | `app/Http/Controllers/API/NotificationController.php` (myNotifications method) |
| Figure 19 | Service Consumption Code | `app/Http/Controllers/API/NotificationController.php` (sendWithFacilityContext method) |

### Code Snippet Index

| Code No. | Description | Source Path | Line Numbers |
|----------|-------------|-------------|--------------|
| Code 1 | UserFactory Implementation | `app/Factories/UserFactory.php` | Lines 1-49 |
| Code 2 | User Entity Class | `app/Models/User.php` | Lines 39-156 |
| Code 3 | Notification Entity Class | `app/Models/Notification.php` | Lines 1-44 |
| Code 4 | SQL Injection Prevention | `app/Http/Controllers/Admin/UserCRUDManagementController.php` | Lines 18-48 |
| Code 5 | Multi-Layer Authorization | `app/Http/Middleware/AdminMiddleware.php` | Lines 16-42 |
| Code 6 | Base Controller Protection | `app/Http/Controllers/Admin/AdminBaseController.php` | Lines 13-41 |
| Code 7 | Method-Level Authorization | `app/Http/Controllers/Admin/UserCRUDManagementController.php` | Lines 97-125 |
| Code 8 | Get User API | `app/Http/Controllers/API/UserController.php` | show() method |
| Code 9 | Get Notifications API | `app/Http/Controllers/API/NotificationController.php` | myNotifications() method |
| Code 10 | Service Consumption | `app/Http/Controllers/API/NotificationController.php` | sendWithFacilityContext() method |

---

## 8. References

Laravel. (2024). *Laravel Documentation* (Version 10.x). Laravel. https://laravel.com/docs/10.x

Gamma, E., Helm, R., Johnson, R., & Vlissides, J. (1994). *Design patterns: Elements of reusable object-oriented software*. Addison-Wesley Professional.

OWASP Foundation. (2021). *OWASP Top 10 - 2021: The Ten Most Critical Web Application Security Risks*. OWASP. https://owasp.org/www-project-top-ten/

Fielding, R. T. (2000). *Architectural styles and the design of network-based software architectures* (Doctoral dissertation, University of California, Irvine).

Mozilla Developer Network. (2024). *HTTP response status codes*. MDN Web Docs. https://developer.mozilla.org/en-US/docs/Web/HTTP/Status

PHP.net. (2024). *PHP: Prepared Statements*. PHP Manual. https://www.php.net/manual/en/pdo.prepared-statements.php

Laravel. (2024). *Laravel Sanctum*. Laravel Documentation. https://laravel.com/docs/10.x/sanctum

Freeman, E., & Robson, E. (2004). *Head first design patterns: A brain-friendly guide*. O'Reilly Media.

Stallings, W. (2017). *Cryptography and network security: Principles and practice* (7th ed.). Pearson.

Richardson, L., & Ruby, S. (2013). *RESTful web APIs: Services for a changing world*. O'Reilly Media.

---

**End of Report**


