<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\AnnouncementController;
use App\Http\Controllers\API\LoyaltyController;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Controllers\API\FacilityController;
use App\Http\Controllers\API\BookingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // User profile routes (All authenticated users) - Must be before /users/{id} routes
    Route::put('/users/profile/update', [UserController::class, 'updateProfile']);
    Route::get('/users/profile/activity-logs', [UserController::class, 'myActivityLogs']);

    // User Management Routes (Admin only)
    Route::prefix('users')->middleware('admin')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::get('/{id}/activity-logs', [UserController::class, 'activityLogs']);
        Route::post('/upload-csv', [UserController::class, 'uploadCsv']);
    });

    // Role Management Routes (Admin only)
    Route::apiResource('roles', RoleController::class)->middleware('admin');

    // Notification Management Routes
    Route::prefix('notifications')->group(function () {
        // Admin only routes
        Route::middleware('admin')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/', [NotificationController::class, 'store']);
            Route::put('/{id}', [NotificationController::class, 'update']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::post('/{id}/send', [NotificationController::class, 'send']);
        });
        // All authenticated users
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::get('/user/my-notifications', [NotificationController::class, 'myNotifications']);
        Route::get('/user/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/user/unread-items', [NotificationController::class, 'getUnreadItems']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/{id}/unread', [NotificationController::class, 'markAsUnread']);
        Route::put('/{id}/acknowledge', [NotificationController::class, 'acknowledge']);
    });

    // Announcement Management Routes
    Route::prefix('announcements')->group(function () {
        // Admin only routes
        Route::middleware('admin')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index']);
            Route::post('/', [AnnouncementController::class, 'store']);
            Route::put('/{id}', [AnnouncementController::class, 'update']);
            Route::delete('/{id}', [AnnouncementController::class, 'destroy']);
            Route::post('/{id}/publish', [AnnouncementController::class, 'publish']);
        });
        // All authenticated users
        Route::get('/{id}', [AnnouncementController::class, 'show']);
        Route::get('/user/my-announcements', [AnnouncementController::class, 'myAnnouncements']);
        Route::get('/user/unread-count', [AnnouncementController::class, 'unreadCount']);
        Route::put('/{id}/read', [AnnouncementController::class, 'markAsRead']);
    });

    // Loyalty Management Routes
    Route::prefix('loyalty')->group(function () {
        // User routes
        Route::get('/points', [LoyaltyController::class, 'getPoints']);
        Route::get('/points/history', [LoyaltyController::class, 'pointsHistory']);
        Route::get('/rewards', [LoyaltyController::class, 'getRewards']);
        Route::post('/rewards/redeem', [LoyaltyController::class, 'redeemReward']);
        Route::get('/certificates', [LoyaltyController::class, 'getCertificates']);
        
        // Admin only routes
        Route::middleware('admin')->group(function () {
            Route::post('/points/award', [LoyaltyController::class, 'awardPoints']);
            Route::post('/certificates/issue', [LoyaltyController::class, 'issueCertificate']);
        });
    });

    // Feedback Management Routes
    Route::prefix('feedbacks')->group(function () {
        // User routes
        Route::post('/', [FeedbackController::class, 'store']);
        Route::get('/user/my-feedbacks', [FeedbackController::class, 'myFeedbacks']);
        Route::get('/{id}', [FeedbackController::class, 'show']);
        
        // Admin only routes
        Route::middleware('admin')->group(function () {
            Route::get('/', [FeedbackController::class, 'index']);
            Route::put('/{id}', [FeedbackController::class, 'update']);
            Route::delete('/{id}', [FeedbackController::class, 'destroy']);
            Route::put('/{id}/respond', [FeedbackController::class, 'respond']);
            Route::put('/{id}/block', [FeedbackController::class, 'block']);
        });
    });

    // Facility Management Routes
    Route::prefix('facilities')->group(function () {
        // All users can view
        Route::get('/', [FacilityController::class, 'index']);
        Route::get('/{id}', [FacilityController::class, 'show']);
        Route::get('/{id}/availability', [FacilityController::class, 'availability']);
        
        // Admin only routes
        Route::middleware('admin')->group(function () {
            Route::post('/', [FacilityController::class, 'store']);
            Route::put('/{id}', [FacilityController::class, 'update']);
            Route::delete('/{id}', [FacilityController::class, 'destroy']);
            Route::get('/{id}/utilization', [FacilityController::class, 'utilization']);
        });
    });

    // Booking & Scheduling Routes
    Route::prefix('bookings')->group(function () {
        // User routes
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/{id}', [BookingController::class, 'show']);
        Route::put('/{id}/cancel', [BookingController::class, 'cancel']);
        Route::get('/user/my-bookings', [BookingController::class, 'myBookings']);
        Route::get('/facility/{facilityId}/availability', [BookingController::class, 'checkAvailability']);
        
        // Admin only routes
        Route::middleware('admin')->group(function () {
            Route::get('/', [BookingController::class, 'index']);
            Route::get('/pending', [BookingController::class, 'getPendingBookings']);
            Route::put('/{id}', [BookingController::class, 'update']);
            Route::put('/{id}/approve', [BookingController::class, 'approve']);
            Route::put('/{id}/reject', [BookingController::class, 'reject']);
        });
    });
});
