<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController; 

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
Route::get('/register', [PageController::class, 'showRegister'])->name('register');

// Dashboard
Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');

// User Management Routes
Route::get('/users', [PageController::class, 'index'])->name('users.index'); 
Route::get('/users/create', [PageController::class, 'create'])->name('users.create'); 
Route::post('/users/store', [PageController::class, 'store'])->name('users.store');

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

// Profile Routes
Route::get('/profile', [PageController::class, 'profile'])->name('profile.index'); 
