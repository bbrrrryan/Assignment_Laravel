<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\Feedback;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Certificate;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // User Management Module Stats
        $stats['total_users'] = User::count();
        $stats['active_users'] = User::where('status', 'active')->count();
        $stats['suspended_users'] = User::where('status', 'suspended')->count();

        // Booking & Scheduling Module Stats
        $stats['total_bookings'] = Booking::count();
        $stats['pending_bookings'] = Booking::where('status', 'pending')->count();
        $stats['approved_bookings'] = Booking::where('status', 'approved')->count();

        // Facility Management Module Stats
        $stats['total_facilities'] = Facility::count();
        $stats['active_facilities'] = Facility::where('status', 'available')->count();
        $stats['maintenance_facilities'] = Facility::where('status', 'maintenance')->count();

        // Notification Management Module Stats
        $stats['total_notifications'] = Notification::count();
        $stats['active_notifications'] = Notification::where('is_active', true)->count();

        // Feedback Management Module Stats
        $stats['total_feedbacks'] = Feedback::count();
        $stats['pending_feedbacks'] = Feedback::where('status', 'pending')->count();
        $stats['blocked_feedbacks'] = Feedback::where('is_blocked', true)->count();

        // Loyalty Management Module Stats
        $stats['total_loyalty_points'] = LoyaltyPoint::sum('points');
        $stats['total_rewards'] = Reward::count();
        $stats['total_certificates'] = Certificate::count();

        // Recent Activity
        $recentUsers = User::latest()->take(5)->get();
        $recentBookings = Booking::with(['user', 'facility'])->latest()->take(5)->get();
        $recentFeedbacks = Feedback::with('user')->latest()->take(5)->get();
        $recentNotifications = Notification::with('creator')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentBookings', 'recentFeedbacks', 'recentNotifications'));
    }
}
