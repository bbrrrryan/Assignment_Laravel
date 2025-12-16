# 4. Design Pattern

## 4.1 Description of Design Pattern

### Factory Pattern (Simple Factory Pattern)

I implemented the **Factory Pattern** (specifically, the Simple Factory Pattern) in both my **User Management Module** and **Announcement Management Module**. This design pattern provides a centralized way to create objects without exposing the object creation logic to the client code.

**In User Management Module:**
The `UserFactory` class handles the creation of user objects with role validation and automatic student ID generation. It ensures that only valid roles (admin, student, staff) are assigned and automatically generates student IDs for student users.

**In Announcement Management Module:**
The `AnnouncementFactory` class handles the creation of announcement objects with type validation, priority validation, and default value assignment.

**Purpose:**
The Factory Pattern is used to encapsulate the creation logic of objects. Instead of directly instantiating objects in controllers, I use factory methods to create them:

**UserFactory** handles:
- Role validation and normalization (admin, student, staff)
- Automatic password hashing
- Student ID generation for student users
- Default status assignment

**AnnouncementFactory** handles:
- Type validation and normalization (info, warning, success, error, reminder, general)
- Priority validation (low, medium, high, urgent)
- Default value assignment
- Consistent object creation across the application

**Benefits:**
1. **Centralized Creation Logic**: All announcement creation logic is in one place, making it easier to maintain and modify
2. **Type Safety**: The factory validates and normalizes announcement types, preventing invalid data
3. **Code Reusability**: The same factory method can be used across different controllers and services
4. **Separation of Concerns**: Controllers don't need to know the details of how announcements are created
5. **Easy to Extend**: Adding new announcement types or validation rules only requires changes in the factory class

---

## 4.2 Implementation of Design Pattern

### Class Diagram

**User Management Module:**

```
┌─────────────────────────────────────────────────────────────┐
│                    PageController                            │
│                  (Client/Controller)                         │
├─────────────────────────────────────────────────────────────┤
│ + createUser(Request): Response                             │
│   - Uses UserFactory to create users                         │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ calls
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      UserFactory                              │
│                    (Factory Class)                            │
├─────────────────────────────────────────────────────────────┤
│ + makeUser(                                                 │
│     type: string,                                            │
│     name: string,                                            │
│     email: string,                                           │
│     password: string                                         │
│   ): User                                                    │
│                                                               │
│ - Validates and normalizes role                             │
│ - Hashes password                                            │
│ - Generates student ID for students                         │
│ - Creates User object                                        │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ creates
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                         User                                  │
│                      (Product Class)                          │
├─────────────────────────────────────────────────────────────┤
│ - id: int                                                    │
│ - name: string                                               │
│ - email: string                                              │
│ - password: string (hashed)                                 │
│ - role: string                                               │
│ - status: string                                             │
│ - studentid: string|null                                    │
│                                                               │
│ + isAdmin(): bool                                            │
│ + isStaff(): bool                                            │
│ + isStudent(): bool                                          │
└─────────────────────────────────────────────────────────────┘
```

**Announcement Management Module:**

```
┌─────────────────────────────────────────────────────────────┐
│                  AnnouncementController                      │
│                  (Client/Controller)                          │
├─────────────────────────────────────────────────────────────┤
│ + store(Request): JsonResponse                              │
│   - Uses AnnouncementFactory to create announcements        │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ calls
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  AnnouncementFactory                          │
│                  (Factory Class)                             │
├─────────────────────────────────────────────────────────────┤
│ + makeAnnouncement(                                         │
│     type: string,                                            │
│     title: string,                                           │
│     content: string,                                         │
│     targetAudience: string,                                   │
│     createdBy: int|null,                                     │
│     priority: string|null,                                   │
│     targetUserIds: array|null,                               │
│     publishedAt: string|null,                                │
│     expiresAt: string|null,                                  │
│     isActive: bool                                           │
│   ): Announcement                                            │
│                                                               │
│ - Validates and normalizes type                             │
│ - Validates priority                                        │
│ - Creates Announcement object                               │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ creates
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Announcement                               │
│                    (Product Class)                            │
├─────────────────────────────────────────────────────────────┤
│ - id: int                                                    │
│ - title: string                                              │
│ - content: string                                           │
│ - type: string                                               │
│ - priority: string|null                                     │
│ - created_by: int                                            │
│ - target_audience: string                                    │
│ - target_user_ids: array|null                               │
│ - published_at: Carbon|null                                 │
│ - expires_at: Carbon|null                                    │
│ - is_active: bool                                            │
│                                                               │
│ + creator(): BelongsTo<User>                                 │
│ + users(): BelongsToMany<User>                               │
└─────────────────────────────────────────────────────────────┘
```

### Implementation Code

**1. UserFactory: `app/Factories/UserFactory.php`**

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

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password), 
            'role' => $roleName,
            'status' => 'active'
        ];
        
        if ($roleName === 'student') {
            $userData['studentid'] = User::generateStudentId();
        }
        
        return User::create($userData);
    }
}
```

**Usage in Controller: `app/Http/Controllers/PageController.php`**

```php
use App\Factories\UserFactory;

public function createUser(Request $request)
{
    $request->validate([
        'name' => 'required',
        'email' => 'required|email',
        'password' => 'required',
        'role' => 'required'
    ]);

    // Use Factory Pattern to create user
    $user = UserFactory::makeUser(
        $request->role,
        $request->name,
        $request->email,
        $request->password
    );

    return redirect()->back()->with('success', 'User created successfully');
}
```

**2. AnnouncementFactory: `app/Factories/AnnouncementFactory.php`**

```php
<?php
/**
 * Author: Liew Zi Li
 * Module: Announcement Management Module
 * Design Pattern: Simple Factory Pattern
 */

namespace App\Factories;

use App\Models\Announcement;

class AnnouncementFactory
{
    /**
     * Create an announcement with type string
     * 
     * @param string $type Announcement type ('info', 'warning', 'success', 'error', 'reminder', 'general')
     * @param string $title
     * @param string $content
     * @param string $targetAudience
     * @param int|null $createdBy
     * @param string|null $priority
     * @param array|null $targetUserIds
     * @param string|null $publishedAt
     * @param string|null $expiresAt
     * @param bool $isActive
     * @return Announcement
     */
    public static function makeAnnouncement($type, $title, $content, $targetAudience, $createdBy = null, $priority = null, $targetUserIds = null, $publishedAt = null, $expiresAt = null, $isActive = true)
    {
        // Normalize announcement type
        $announcementType = strtolower(trim($type));
        
        // Validate type - using simple if-else
        if ($announcementType === 'info') {
            $typeName = 'info';
        } elseif ($announcementType === 'warning') {
            $typeName = 'warning';
        } elseif ($announcementType === 'success') {
            $typeName = 'success';
        } elseif ($announcementType === 'error') {
            $typeName = 'error';
        } elseif ($announcementType === 'reminder') {
            $typeName = 'reminder';
        } elseif ($announcementType === 'general') {
            $typeName = 'general';
        } else {
            // Default to general if invalid
            $typeName = 'general';
        }

        // Validate priority - using simple if-else
        $priorityName = null;
        if ($priority !== null) {
            $priorityLower = strtolower(trim($priority));
            if ($priorityLower === 'low' || $priorityLower === 'medium' || $priorityLower === 'high' || $priorityLower === 'urgent') {
                $priorityName = $priorityLower;
            }
        }

        return Announcement::create([
            'title' => $title,
            'content' => $content,
            'type' => $typeName,
            'priority' => $priorityName,
            'created_by' => $createdBy,
            'target_audience' => $targetAudience,
            'target_user_ids' => $targetUserIds,
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
            'is_active' => $isActive,
        ]);
    }
}
```

**Usage in Controller: `app/Http/Controllers/API/AnnouncementController.php`**

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'type' => 'required|in:info,warning,success,error,reminder,general',
        'priority' => 'nullable|in:low,medium,high,urgent',
        'target_audience' => 'required|in:all,students,staff,admins,specific',
        'target_user_ids' => 'nullable|array',
        'target_user_ids.*' => 'exists:users,id',
        'published_at' => 'nullable|date',
        'expires_at' => 'nullable|date|after:published_at',
        'is_active' => 'nullable|boolean',
    ]);

    // Use Factory Pattern to create announcement
    $announcement = AnnouncementFactory::makeAnnouncement(
        $validated['type'],
        $validated['title'],
        $validated['content'],
        $validated['target_audience'],
        auth()->id(),
        $validated['priority'] ?? null,
        $validated['target_user_ids'] ?? null,
        $validated['published_at'] ?? null,
        $validated['expires_at'] ?? null,
        $request->is_active ?? true
    );

    return response()->json([
        'message' => 'Announcement created successfully',
        'data' => $announcement->load('creator'),
    ], 201);
}
```

### Justification for Choice of Design Pattern

**Why Factory Pattern?**

1. **Complex Object Creation Logic**
   - **Users** require role validation, password hashing, and automatic student ID generation
   - **Announcements** require type validation, priority validation, and default value assignment
   - Without factories, this logic would be duplicated in every controller that creates these objects
   - The factories centralize this logic, making it easier to maintain

2. **Type Safety and Validation**
   - **UserFactory** ensures that only valid roles (admin, student, staff) are assigned
   - Invalid roles are automatically normalized to 'student', preventing data inconsistencies
   - **AnnouncementFactory** ensures that only valid announcement types (info, warning, success, error, reminder, general) are created
   - Invalid types are automatically normalized to 'general', preventing data inconsistencies
   - Priority values are validated to ensure they match allowed values (low, medium, high, urgent)

3. **Separation of Concerns**
   - Controllers focus on handling HTTP requests and responses
   - The factories handle the business logic of creating users and announcements
   - This separation makes the code more maintainable and testable

4. **Consistency Across the Application**
   - All users and announcements are created using the same factory methods
   - This ensures consistent behavior regardless of where these objects are created
   - Changes to creation logic only need to be made in one place (the factory)

5. **Easy to Extend**
   - If new user roles, announcement types, or validation rules are needed, only the factories need to be modified
   - Controllers using the factories automatically benefit from these changes
   - No need to modify multiple controllers

6. **Reusability**
   - **UserFactory** can be used in:
     - `PageController::createUser()` - Creating new users
     - Any other controller or service that needs to create users
   - **AnnouncementFactory** is used in:
     - `AnnouncementController::store()` - Creating new announcements
     - `AnnouncementController::update()` - Updating existing announcements
     - Any other service or controller that needs to create announcements

**Alternative Approaches Considered:**

- **Direct Model Creation**: Creating users and announcements directly using `User::create()` or `Announcement::create()` would require duplicating validation logic in every controller
- **Service Class**: While a service class could work, the Factory Pattern is more appropriate here because the primary concern is object creation, not business logic orchestration

**Conclusion:**

The Factory Pattern is the most suitable design pattern for both user and announcement creation because it:
- Encapsulates complex creation logic (role validation, type validation, password hashing, student ID generation)
- Ensures type safety and data consistency
- Provides a clean, reusable interface for creating objects throughout the application
- Makes the code more maintainable and easier to extend

By implementing the Factory Pattern in both modules, I ensure consistent object creation practices across the entire system, reducing code duplication and improving maintainability.

