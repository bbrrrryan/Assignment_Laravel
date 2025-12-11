<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserCRUDManagementController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [PageController::class, 'home'])->name('home');

// Authentication Routes
Route::get('/login', [PageController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [PageController::class, 'showRegister'])->name('register');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    
    // Admin Routes - Only admin can access
    Route::prefix('admin')->middleware('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        
        // User Management
        Route::resource('users', UserCRUDManagementController::class);
        Route::post('users/upload-csv', [UserCRUDManagementController::class, 'uploadCsv'])->name('users.upload-csv');
    });
    
    // Profile Routes (All authenticated users)
    Route::get('/profile', [PageController::class, 'profile'])->name('profile.index');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings.index');
    
    // Facilities Routes
    Route::get('/facilities', [PageController::class, 'facilities'])->name('facilities.index');
    Route::get('/facilities/{id}', [PageController::class, 'showFacility'])->name('facilities.show');

    // Bookings Routes
    Route::get('/bookings', [PageController::class, 'bookings'])->name('bookings.index');
    Route::get('/bookings/{id}', [PageController::class, 'showBooking'])->name('bookings.show');

    // Notifications Routes
    Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications.index');
    Route::get('/notifications/{id}', [PageController::class, 'showNotification'])->name('notifications.show');

    // Loyalty Routes
    Route::get('/loyalty', [PageController::class, 'loyalty'])->name('loyalty.index');

    // Feedback Routes
    Route::get('/feedbacks', [PageController::class, 'feedbacks'])->name('feedbacks.index');
    Route::get('/feedbacks/{id}', [PageController::class, 'showFeedback'])->name('feedbacks.show');
    
    // Smart dashboard route - redirects based on user role
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $role = strtolower($user->role ?? '');
        
        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        
        return redirect()->route('home');
    })->name('dashboard');
}); 
