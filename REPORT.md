# TARUMT Facilities Management System - Report

## 1. Introduction to the System

The TARUMT Facilities Management System is basically a web platform that helps manage campus facilities and bookings. So what it does is, students and staff can book facilities like computer labs, meeting rooms, and other campus spaces online instead of using paper forms.

I built this system using Laravel, which is a PHP framework. It uses REST API architecture, meaning the frontend and backend talk to each other through API calls. The main idea is to make facility booking easier and more organized for everyone.

There are three types of users in the system. We have admins who can manage everything, staff members, and students who can book facilities. Each user has their own role, so they can only do things that match their permissions.

The system has several important features. Users can register and login with email verification using OTP codes. They can manage their own profiles and check their activity history. Admins can create announcements and notifications to inform everyone about important stuff. There's also a booking system where users can reserve facilities, and admins can approve or reject these bookings.

Overall, this system makes campus facility management way simpler. Before this, people probably had to fill out forms manually or call someone to book a facility. Now everything is done online through a web interface, which saves a lot of time for both users and administrators.

---

## 2. Module Description

I was responsible for developing two main modules in this system: User Management and Announcement Management. Let me walk you through each one and explain what they do, separated by user roles.

### 2.1 User Management Module

This module handles all user-related operations in the system. It includes authentication features for all users and administrative functions for admins.

#### User (Regular Users)

**a) Registration**
- Users can create a new account by providing their name, email, and password
- The system checks if the email is already registered
- After registration, an OTP code is generated and sent to the user's email for verification
- The account status is set to 'inactive' until the OTP is verified
- **Screenshot Path:** `resources/views/auth/register.blade.php`
- **Class Path:** `app/Http/Controllers/API/AuthController.php` (register method, lines 19-98)

**b) EMAIL OTP (Account Verification)**
- After registration, users need to verify their account using the 6-digit OTP sent to their email
- On the OTP page, users enter their email and the OTP code
- The system checks if the OTP matches and is not expired
- If everything is correct, the account status is updated to 'active' and the OTP fields are cleared
- If the OTP is wrong or expired, a friendly error message is shown
- **Screenshot Path:** `resources/views/auth/verify-otp.blade.php`
- **Class Path:** `app/Http/Controllers/AuthController.php` (verifyOtp method, lines 118-166)

**c) Resend OTP**
- If users don't receive the OTP email, they can request a new one
- The system generates a new 6-digit OTP code
- The OTP expires after 3 minutes
- Users can only resend OTP if their account is still inactive
- **Screenshot Path:** `resources/views/auth/verify-otp.blade.php` (Resend OTP button)
- **Class Path:** `app/Http/Controllers/API/AuthController.php` (resendOtp method, lines 177-222)

**d) Login**
- Users can login with their email and password
- The system verifies the credentials and checks if the account is active
- Only active accounts can login
- After successful login, the system records the login time and generates an authentication token for API calls
- The login action is logged with IP address and browser information
- **Screenshot Path:** `resources/views/auth/login.blade.php`
- **Class Path:** `app/Http/Controllers/API/AuthController.php` (login method, lines 130-168)

**e) Remember Me**
- On the web login page, there is a \"Remember Me\" checkbox
- If the user ticks this option, the system keeps them logged in even after they close the browser
- This is done by creating a long-lived session for the user
- The feature still respects account status and permissions
- **Screenshot Path:** `resources/views/auth/login.blade.php` (Remember Me checkbox)
- **Class Path:** `app/Http/Controllers/AuthController.php` (login method, line 61 uses `Auth::login($user, $request->filled('remember'))`)

**f) Logout**
- Users can logout from the system
- The logout action is recorded in the activity log
- The authentication token and session are cleared so the user is fully logged out
- **Screenshot Path:** `resources/views/layouts/app.blade.php` (Logout button)
- **Class Path:** `app/Http/Controllers/API/AuthController.php` (logout method, lines 173-187) and `app/Http/Controllers/AuthController.php` (logout method, lines 88-113)

**g) User Profile**
- Users can view their own profile information
- It shows name, email, role, phone number, address, and student ID (if they are a student)
- This information is used by the frontend profile page
- **Screenshot Path:** `resources/views/profile/index.blade.php`
- **Class Path:** `app/Http/Controllers/API/AuthController.php` (me method, lines 192-199)

**h) Update Profile**
- Users can update their personal information like name, phone number, and address
- They can also change their password if needed
- When changing password, they need to confirm it by entering it twice
- All profile updates are logged in the activity log
- **Screenshot Path:** `resources/views/profile/index.blade.php` (Update Profile Form)
- **Class Path:** `app/Http/Controllers/API/UserController.php` (updateProfile method, lines 377-418)

**i) My Activity Logs**
- Users can view their own activity history
- Shows the last 30 activities they performed
- Includes pagination with 10 records per page
- Activities are sorted by newest first
- Shows actions like login, logout, profile updates, and booking activities
- **Screenshot Path:** `resources/views/profile/index.blade.php` (Activity Log Section)
- **Class Path:** `app/Http/Controllers/API/UserController.php` (myActivityLogs method, lines 424-472)

#### Admin (Administrators)

**a) User List**
- Admins can see all users displayed in a table
- The list has pagination, so you see 10 users per page by default
- You can filter users by status (active or inactive) and by role (admin, student, or staff)
- There's a search box where you can type a name or email to find users quickly
- You can sort the list by different fields like name, email, role, or when they were created
- **Screenshot Path:** `resources/views/admin/users/index.blade.php`
- **Class Path:** `app/Http/Controllers/API/UserController.php` (index method, lines 18-64)

**b) Create User**
- Admins can add new users to the system
- When creating a user, you need to provide name, email, password, and role
- The system validates that the email is unique and the password meets requirements
- Phone number and address are optional fields
- The password is automatically encrypted before saving
- All user creation activities are logged in the activity log
- **Screenshot Path:** `resources/views/admin/users/index.blade.php` (Create User Modal)
- **Class Path:** `app/Http/Controllers/API/UserController.php` (store method, lines 69-115)

**c) View User Details**
- Admins can view detailed information about a specific user
- Shows user profile, role, status, and other details
- Can also see the user's activity logs
- **Screenshot Path:** `resources/views/admin/users/show.blade.php`
- **Class Path:** `app/Http/Controllers/API/UserController.php` (show method, lines 120-132)

**d) Update User**
- Admins can edit user information
- You can update name, email, role, phone number, address, and status
- The system only updates the fields that are provided, so partial updates are possible
- All changes are tracked in the activity log
- **Screenshot Path:** `resources/views/admin/users/edit.blade.php`
- **Class Path:** `app/Http/Controllers/API/UserController.php` (update method, lines 137-183)

**e) Delete User**
- Admins can remove users from the system
- Before deletion, the action is recorded in the activity log
- **Screenshot Path:** `resources/views/admin/users/index.blade.php` (Delete button in table)
- **Class Path:** `app/Http/Controllers/API/UserController.php` (destroy method, lines 188-208)

**f) CSV Bulk Upload**
- This is probably the most useful feature when you need to add many users at once
- Admins can upload a CSV file with all the user data
- The system reads through the file and creates all users automatically
- If there are any errors in the file, it tells you which rows had problems
- For passwords, here's how it works: if password is in the file, use that; if not but phone number exists, use phone number as password; otherwise it uses a default password
- **Screenshot Path:** `resources/views/admin/users/index.blade.php` (CSV Upload Section)
- **Class Path:** `app/Http/Controllers/API/UserController.php` (uploadCsv method, lines 232-372)

**g) Export CSV**
- Admins can download all user data as a CSV file
- Useful for backup or external processing
- The file name includes timestamp for easy identification
- **Screenshot Path:** `resources/views/admin/users/index.blade.php` (Export CSV button)
- **Class Path:** `app/Http/Controllers/Admin/UserCRUDManagementController.php` (exportCsv method, lines 139-173)

**h) User Activity Logs**
- Every user has an activity log that keeps track of everything they do
- Admins can check what actions a specific user has performed
- The log shows things like when they logged in, logged out, updated their profile, or made bookings
- It also records timestamps, IP addresses, and what browser they were using
- **Screenshot Path:** `resources/views/admin/users/show.blade.php` (Activity Log Section)
- **Class Path:** `app/Http/Controllers/API/UserController.php` (activityLogs method, lines 213-227)

---

### 2.2 Announcement Management Module

This module handles system announcements. Announcements are kind of similar to notifications, but they're more like public posts that everyone can view.

#### User (Regular Users)

**a) View Announcement**
- Users can view detailed information about an announcement
- When someone views an announcement, the view count goes up automatically
- It shows all the announcement details including title, content, type, priority, and who created it
- **Screenshot Path:** `resources/views/announcements/show.blade.php`
- **Class Path:** `app/Http/Controllers/API/AnnouncementController.php` (show method, lines 95-106)

**b) My Announcements**
- Users can see announcements that were sent to them
- Shows all announcements they received, with pagination support
- Can filter by read/unread status
- Activities are sorted by newest first
- **Screenshot Path:** `resources/views/announcements/index.blade.php` (if exists)
- **Class Path:** `app/Http/Controllers/API/AnnouncementController.php` (myAnnouncements method, lines 211-230)

**c) Unread Count**
- Shows how many unread announcements each user has
- This count is displayed as a badge in the navigation bar
- Updates automatically when announcements are read
- **Screenshot Path:** `resources/views/layouts/app.blade.php` (Announcement badge)
- **Class Path:** `app/Http/Controllers/API/AnnouncementController.php` (unreadCount method, lines 192-206)

**d) Mark as Read/Unread**
- Users can mark announcements as read or unread
- When marked as read, the system records the read timestamp
- Helps users keep track of which announcements they've seen
- **Screenshot Path:** `resources/views/announcements/index.blade.php` (Mark as Read button)
- **Class Path:** `app/Http/Controllers/API/AnnouncementController.php` (markAsRead/markAsUnread methods)

#### Admin (Administrators)

**a) Announcement List**
- Admins can see all announcements in the system
- Supports search by title or content
- Can filter by type (info, warning, success, error, reminder, general) and priority
- Can filter by active/inactive status
- Supports sorting by different fields
- Uses pagination with 10 announcements per page
- **Screenshot Path:** `resources/views/admin/announcements/index.blade.php`
- **Class Path:** `app/Http/Controllers/API/AnnouncementController.php` (index method, lines 17-52)

**b) Create Announcement**
- Admins can create new announcements
- Need to provide title, content, type, and target audience
- Target audience can be: all users, students only, staff only, admins only, or specific users
- Can set priority level and schedule publication date
- Can set expiration date
- Uses Factory Pattern to create announcement objects
- **Screenshot Path:** `resources/views/admin/announcements/index.blade.php` (Create Announcement Modal)
- **Class Path:** `app/Http/Controllers/API/AnnouncementController.php` (store method, lines 57-90)

**c) Update Announcement**
- Admins can edit existing announcements
- Can update title, content, type, priority, and other details
- Can modify target audience and expiration date
- **Screenshot Path:** `resources/views/admin/announcements/edit.blade.php`
- **Class Path:** `app/Http/Controllers/API/AnnouncementController.php` (update method)

**d) Delete Announcement**
- Admins can remove announcements from the system
- Deletion removes the announcement and all its user associations
- **Screenshot Path:** `resources/views/admin/announcements/index.blade.php` (Delete button)
- **Class Path:** `app/Http/Controllers/API/AnnouncementController.php` (destroy method)

**e) Publish Announcement**
- After creating an announcement, admins need to publish it
- The system finds target users based on the target audience setting
- It links the announcement to those users in the database
- Sets the published_at timestamp
- Once published, users will see the announcement in their announcement list
- **Screenshot Path:** `resources/views/admin/announcements/show.blade.php` (Publish button)
- **Class Path:** `app/Http/Controllers/API/AnnouncementController.php` (publish method, lines 152-187)

---

## Summary

So these two modules work together to provide a complete user management and communication system. The User Management module handles authentication for all users and administrative functions for admins. The Announcement Management module allows admins to create and publish announcements, while users can view and manage their received announcements.

All the modules follow REST API principles and use Laravel's built-in features like Eloquent ORM for database stuff, validation to check inputs, and pagination to handle large lists. The code is organized using MVC pattern, which makes it easier to maintain and add new features later on.

