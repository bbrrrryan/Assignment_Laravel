# TARUMT Facilities Management System - API Documentation & Theory Guide

## Table of Contents
1. [System Overview](#system-overview)
2. [Architecture & Design Patterns](#architecture--design-patterns)
3. [Database Design](#database-design)
4. [REST API Principles](#rest-api-principles)
5. [Laravel Concepts Used](#laravel-concepts-used)
6. [Module Documentation](#module-documentation)
7. [API Endpoints Reference](#api-endpoints-reference)
8. [Authentication & Authorization](#authentication--authorization)
9. [Best Practices](#best-practices)

---

## System Overview

The TARUMT Facilities Management System is a comprehensive web-based platform built using Laravel 10 with REST API architecture. The system manages campus facilities, user interactions, bookings, feedback, loyalty programs, and notifications.

### Key Features:
- **User Management**: Role-based access control, user activity tracking
- **Facility Management**: Facility CRUD operations, availability tracking
- **Booking System**: Facility booking with approval workflow
- **Notification System**: Multi-channel notifications with templates
- **Loyalty Program**: Points system with rewards and certificates
- **Feedback Management**: User feedback with admin moderation

---

## Architecture & Design Patterns

### 1. **MVC (Model-View-Controller) Pattern**
Laravel follows the MVC architectural pattern:
- **Models**: Represent data structures (Eloquent ORM)
- **Controllers**: Handle business logic and HTTP requests
- **Views**: Presentation layer (Blade templates for web, JSON for API)

### 2. **RESTful API Design**
The system follows REST (Representational State Transfer) principles:
- **Resources**: Entities like users, facilities, bookings
- **HTTP Methods**: GET (read), POST (create), PUT (update), DELETE (destroy)
- **Stateless**: Each request contains all necessary information
- **Uniform Interface**: Consistent URL structure and response format

### 3. **Repository Pattern** (Implicit)
Laravel's Eloquent ORM acts as a repository layer, abstracting database operations.

### 4. **Service Layer Pattern** (Recommended for future)
Business logic can be extracted into service classes for better organization.

---

## Database Design

### Entity Relationship Overview

```
Users (1) ──< (N) UserActivityLogs
Users (1) ──< (N) Bookings
Users (1) ──< (N) Feedbacks
Users (1) ──< (N) LoyaltyPoints
Users (1) ──< (N) Certificates
Users (N) >──< (N) Notifications (Many-to-Many)
Users (N) >──< (N) Rewards (Many-to-Many)

Roles (1) ──< (N) Users
Roles (N) >──< (N) Permissions (Many-to-Many)

Facilities (1) ──< (N) Bookings
Facilities (1) ──< (N) Feedbacks

Bookings (1) ──< (N) BookingStatusHistory
Rewards (1) ──< (N) Certificates
```

### Key Database Concepts

#### 1. **Foreign Keys & Relationships**
- **One-to-Many**: User has many Bookings
- **Many-to-Many**: Users and Notifications (via pivot table)
- **Polymorphic**: LoyaltyPoints can relate to different models

#### 2. **Indexes**
Indexes are created on frequently queried columns:
- `user_id`, `facility_id`, `status`, `booking_date`
- Improves query performance significantly

#### 3. **Soft Deletes** (Optional Enhancement)
Consider implementing soft deletes for important records to maintain audit trails.

---

## REST API Principles

### HTTP Status Codes
- **200 OK**: Successful GET, PUT requests
- **201 Created**: Successful POST requests
- **204 No Content**: Successful DELETE requests
- **400 Bad Request**: Invalid request data
- **401 Unauthorized**: Missing or invalid authentication
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource doesn't exist
- **422 Unprocessable Entity**: Validation errors
- **500 Internal Server Error**: Server-side errors

### URL Structure
```
/api/{resource}              # List/Create
/api/{resource}/{id}         # Show/Update/Delete
/api/{resource}/{id}/{action} # Custom actions
```

### Request/Response Format
**Request Headers:**
```
Content-Type: application/json
Authorization: Bearer {token}
Accept: application/json
```

**Response Format:**
```json
{
    "message": "Success message",
    "data": { ... },
    "errors": { ... }  // Only on validation errors
}
```

---

## Laravel Concepts Used

### 1. **Eloquent ORM (Object-Relational Mapping)**
Eloquent provides an ActiveRecord implementation for database operations:

```php
// Instead of raw SQL:
// SELECT * FROM users WHERE status = 'active'

// We use:
User::where('status', 'active')->get();
```

**Benefits:**
- Type-safe queries
- Relationship management
- Automatic timestamps
- Mass assignment protection

### 2. **Migrations**
Database version control system:
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    // ...
});
```

**Benefits:**
- Version control for database schema
- Team collaboration
- Easy rollback capabilities

### 3. **Middleware**
HTTP request filters:
- `auth:sanctum`: Authentication check
- `throttle`: Rate limiting
- Custom middleware for authorization

### 4. **Validation**
Request validation using Form Requests or inline validation:
```php
$request->validate([
    'email' => 'required|email|unique:users',
    'password' => 'required|min:6',
]);
```

### 5. **Laravel Sanctum**
Token-based authentication for APIs:
- Generates secure tokens
- Token expiration management
- Per-user token tracking

### 6. **Resource Controllers**
RESTful controller structure:
- `index()`: List resources
- `store()`: Create resource
- `show()`: Display single resource
- `update()`: Update resource
- `destroy()`: Delete resource

---

## Module Documentation

### 1. User Management Module

#### Features:
- User CRUD operations
- Role assignment
- Account status management (active, suspended, deactivated)
- Activity logging
- CSV bulk import

#### Database Tables:
- `users`: Core user information
- `roles`: User roles (admin, student, staff)
- `permissions`: System permissions
- `role_permission`: Many-to-many relationship
- `user_activity_logs`: User action tracking

#### Key Endpoints:
```
POST   /api/register              # Register new user
POST   /api/login                  # User login
GET    /api/users                  # List users (with filters)
POST   /api/users                  # Create user (admin)
GET    /api/users/{id}             # Get user details
PUT    /api/users/{id}             # Update user
DELETE /api/users/{id}             # Delete user
GET    /api/users/{id}/activity-logs # User activity history
```

#### Theory:
- **Role-Based Access Control (RBAC)**: Users are assigned roles, roles have permissions
- **Activity Logging**: Every significant action is logged for audit purposes
- **Soft Status Management**: Users can be suspended without deletion

---

### 2. Notification Management Module

#### Features:
- Create and send notifications
- Template-based notifications
- Scheduled notifications
- User-specific or broadcast notifications
- Read/acknowledge tracking

#### Database Tables:
- `notifications`: Notification content
- `notification_templates`: Reusable templates
- `user_notification`: Many-to-many with read status

#### Key Endpoints:
```
GET    /api/notifications                    # List notifications
POST   /api/notifications                    # Create notification
POST   /api/notifications/{id}/send          # Send notification
GET    /api/notifications/user/my-notifications # User's notifications
PUT    /api/notifications/{id}/read          # Mark as read
PUT    /api/notifications/{id}/acknowledge  # Acknowledge notification
```

#### Theory:
- **Observer Pattern**: Can be used to auto-send notifications on events
- **Queue System**: For scheduled/background notifications
- **Template Engine**: Variable substitution in templates

---

### 3. Loyalty Management Module

#### Features:
- Points earning system
- Rewards catalog
- Certificate issuance
- Points redemption

#### Database Tables:
- `loyalty_points`: Points transactions
- `rewards`: Available rewards
- `certificates`: Issued certificates
- `user_reward`: Redemption tracking

#### Key Endpoints:
```
GET    /api/loyalty/points                   # Get user points
GET    /api/loyalty/points/history           # Points history
POST   /api/loyalty/points/award              # Award points (admin)
GET    /api/loyalty/rewards                   # List rewards
POST   /api/loyalty/rewards/redeem            # Redeem reward
GET    /api/loyalty/certificates              # User certificates
POST   /api/loyalty/certificates/issue        # Issue certificate (admin)
```

#### Theory:
- **Transaction System**: Points are tracked as transactions
- **Polymorphic Relations**: Points can be awarded for different actions
- **Approval Workflow**: Certificates require admin approval

---

### 4. Feedback Management Module

#### Features:
- Submit feedback/complaints
- Rating system
- Admin response
- Content moderation (block inappropriate content)

#### Database Tables:
- `feedbacks`: Feedback content
- Related to `users`, `facilities`, `bookings`

#### Key Endpoints:
```
GET    /api/feedbacks                        # List feedbacks
POST   /api/feedbacks                        # Submit feedback
PUT    /api/feedbacks/{id}/respond           # Admin response
PUT    /api/feedbacks/{id}/block              # Block feedback
```

#### Theory:
- **Moderation System**: Admin can review and block content
- **Status Workflow**: pending → under_review → resolved/rejected
- **Rating Aggregation**: Can calculate average ratings

---

### 5. Facility Management Module

#### Features:
- Facility CRUD operations
- Availability tracking
- Utilization reports
- Maintenance scheduling

#### Database Tables:
- `facilities`: Facility information
- Related to `bookings` for availability

#### Key Endpoints:
```
GET    /api/facilities                       # List facilities
POST   /api/facilities                       # Create facility
GET    /api/facilities/{id}/availability      # Check availability
GET    /api/facilities/{id}/utilization       # Utilization stats
```

#### Theory:
- **Availability Calculation**: Based on bookings and maintenance schedules
- **JSON Fields**: `available_times`, `equipment` stored as JSON
- **Status Management**: available, maintenance, unavailable, reserved

---

### 6. Booking & Scheduling Module

#### Features:
- Create bookings
- Approval workflow
- Cancellation handling
- Status history tracking
- Conflict detection

#### Database Tables:
- `bookings`: Booking information
- `booking_status_history`: Status change audit trail

#### Key Endpoints:
```
GET    /api/bookings                         # List bookings
POST   /api/bookings                         # Create booking
PUT    /api/bookings/{id}/approve             # Approve booking
PUT    /api/bookings/{id}/reject              # Reject booking
PUT    /api/bookings/{id}/cancel              # Cancel booking
GET    /api/bookings/user/my-bookings         # User's bookings
GET    /api/bookings/facility/{id}/availability # Check availability
```

#### Theory:
- **State Machine**: Booking status transitions (pending → approved/rejected → completed/cancelled)
- **Conflict Detection**: Check for overlapping bookings
- **Audit Trail**: Status history maintains complete record
- **Auto-reminders**: 24-hour reminder notifications (can be implemented with scheduled tasks)

---

## API Endpoints Reference

### Authentication
```
POST   /api/register
POST   /api/login
POST   /api/logout          [Auth Required]
GET    /api/me              [Auth Required]
```

### Users
```
GET    /api/users                           [Auth Required]
POST   /api/users                           [Auth Required - Admin]
GET    /api/users/{id}                      [Auth Required]
PUT    /api/users/{id}                      [Auth Required - Admin]
DELETE /api/users/{id}                      [Auth Required - Admin]
GET    /api/users/{id}/activity-logs       [Auth Required]
POST   /api/users/upload-csv                [Auth Required - Admin]
PUT    /api/users/profile/update            [Auth Required]
```

### Roles
```
GET    /api/roles                           [Auth Required]
POST   /api/roles                           [Auth Required - Admin]
GET    /api/roles/{id}                      [Auth Required]
PUT    /api/roles/{id}                      [Auth Required - Admin]
DELETE /api/roles/{id}                      [Auth Required - Admin]
```

### Notifications
```
GET    /api/notifications                    [Auth Required]
POST   /api/notifications                    [Auth Required - Admin]
GET    /api/notifications/{id}               [Auth Required]
PUT    /api/notifications/{id}               [Auth Required - Admin]
DELETE /api/notifications/{id}               [Auth Required - Admin]
POST   /api/notifications/{id}/send          [Auth Required - Admin]
GET    /api/notifications/user/my-notifications [Auth Required]
PUT    /api/notifications/{id}/read          [Auth Required]
PUT    /api/notifications/{id}/acknowledge   [Auth Required]
```

### Loyalty
```
GET    /api/loyalty/points                   [Auth Required]
GET    /api/loyalty/points/history          [Auth Required]
POST   /api/loyalty/points/award            [Auth Required - Admin]
GET    /api/loyalty/rewards                  [Auth Required]
POST   /api/loyalty/rewards/redeem           [Auth Required]
GET    /api/loyalty/certificates             [Auth Required]
POST   /api/loyalty/certificates/issue       [Auth Required - Admin]
```

### Feedback
```
GET    /api/feedbacks                       [Auth Required]
POST   /api/feedbacks                       [Auth Required]
GET    /api/feedbacks/{id}                  [Auth Required]
PUT    /api/feedbacks/{id}                  [Auth Required]
DELETE /api/feedbacks/{id}                  [Auth Required - Admin]
PUT    /api/feedbacks/{id}/respond          [Auth Required - Admin]
PUT    /api/feedbacks/{id}/block            [Auth Required - Admin]
```

### Facilities
```
GET    /api/facilities                      [Auth Required]
POST   /api/facilities                      [Auth Required - Admin]
GET    /api/facilities/{id}                 [Auth Required]
PUT    /api/facilities/{id}                 [Auth Required - Admin]
DELETE /api/facilities/{id}                 [Auth Required - Admin]
GET    /api/facilities/{id}/availability     [Auth Required]
GET    /api/facilities/{id}/utilization      [Auth Required - Admin]
```

### Bookings
```
GET    /api/bookings                        [Auth Required]
POST   /api/bookings                        [Auth Required]
GET    /api/bookings/{id}                   [Auth Required]
PUT    /api/bookings/{id}                   [Auth Required]
DELETE /api/bookings/{id}                   [Auth Required]
PUT    /api/bookings/{id}/approve            [Auth Required - Admin]
PUT    /api/bookings/{id}/reject             [Auth Required - Admin]
PUT    /api/bookings/{id}/cancel             [Auth Required]
GET    /api/bookings/user/my-bookings        [Auth Required]
GET    /api/bookings/facility/{facilityId}/availability [Auth Required]
```

---

## Authentication & Authorization

### Laravel Sanctum
Token-based authentication for SPA and mobile applications.

#### How It Works:
1. User logs in with credentials
2. Server validates and returns a token
3. Client stores token (localStorage, secure cookie)
4. Subsequent requests include token in Authorization header
5. Server validates token on each request

#### Token Management:
```php
// Create token
$token = $user->createToken('auth_token')->plainTextToken;

// Validate token (automatic via middleware)
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});

// Revoke token
$request->user()->currentAccessToken()->delete();
```

### Authorization
Role-based permissions:
```php
// Check permission
if ($user->hasPermission('manage_users')) {
    // Allow action
}
```

---

## Best Practices

### 1. **API Versioning**
Consider versioning your API:
```
/api/v1/users
/api/v2/users
```

### 2. **Rate Limiting**
Protect against abuse:
```php
Route::middleware('throttle:60,1')->group(function () {
    // 60 requests per minute
});
```

### 3. **API Resources**
Use API Resources for consistent response formatting:
```php
// app/Http/Resources/UserResource.php
return new UserResource($user);
```

### 4. **Request Validation**
Always validate input:
```php
$request->validate([
    'email' => 'required|email|unique:users',
]);
```

### 5. **Error Handling**
Consistent error responses:
```php
return response()->json([
    'message' => 'Error message',
    'errors' => $validator->errors(),
], 422);
```

### 6. **Database Transactions**
For complex operations:
```php
DB::transaction(function () {
    // Multiple database operations
});
```

### 7. **Eager Loading**
Prevent N+1 query problems:
```php
User::with('role', 'bookings')->get();
```

### 8. **Pagination**
Always paginate large datasets:
```php
$users = User::paginate(15);
```

### 9. **Soft Deletes**
For important records:
```php
use SoftDeletes;
```

### 10. **Logging**
Log important events:
```php
Log::info('User created', ['user_id' => $user->id]);
```

---

## Testing the API

### Using Postman/Insomnia:
1. **Register/Login** to get token
2. **Copy token** from response
3. **Set Authorization header**: `Bearer {token}`
4. **Make requests** to protected endpoints

### Example cURL:
```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Get users (with token)
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Future Enhancements

1. **API Versioning**: Implement versioning strategy
2. **API Resources**: Create resource classes for consistent responses
3. **Queue System**: Implement background jobs for notifications
4. **Event System**: Use Laravel events for decoupled functionality
5. **Caching**: Implement Redis caching for frequently accessed data
6. **Search**: Implement full-text search (Laravel Scout)
7. **File Storage**: Implement file uploads for certificates, facility images
8. **Real-time**: WebSocket integration for real-time notifications
9. **Testing**: Comprehensive test suite (Feature & Unit tests)
10. **Documentation**: API documentation using Swagger/OpenAPI

---

## Conclusion

This system demonstrates:
- **RESTful API design** principles
- **Laravel best practices** and conventions
- **Database design** with proper relationships
- **Security** through authentication and authorization
- **Scalability** through proper architecture
- **Maintainability** through clean code structure

The modular design allows for easy extension and maintenance, following SOLID principles and Laravel conventions.

---

**Author**: System Development Team  
**Framework**: Laravel 10  
**Architecture**: REST API  
**Database**: MySQL  
**Authentication**: Laravel Sanctum

