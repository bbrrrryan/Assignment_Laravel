<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 * Admin Dashboard Controller
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Facility;
use App\Models\Feedback;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Certificate;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        // User Management Module Stats
        $stats = [
            // User Management
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            
            // Booking & Scheduling Module
            'total_bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'approved_bookings' => Booking::where('status', 'approved')->count(),
            
            // Facility Management Module
            'total_facilities' => Facility::count(),
            'active_facilities' => Facility::where('status', 'available')->count(),
            'maintenance_facilities' => Facility::where('status', 'maintenance')->count(),
            
            // Notification Management Module
            'total_notifications' => Notification::count(),
            'active_notifications' => Notification::where('is_active', true)->count(),
            
            // Feedback Management Module
            'total_feedbacks' => Feedback::count(),
            'pending_feedbacks' => Feedback::where('status', 'pending')->count(),
            'blocked_feedbacks' => Feedback::where('is_blocked', true)->count(),
            
            // Loyalty Management Module
            'total_loyalty_points' => LoyaltyPoint::sum('points'),
            'total_rewards' => Reward::count(),
            'active_rewards' => Reward::where('is_active', true)->count(),
            'total_certificates' => Certificate::count(),
        ];

        // Recent users
        $recentUsers = User::with('role')
            ->latest()
            ->limit(5)
            ->get();

        // Recent bookings
        $recentBookings = Booking::with(['user.role', 'facility'])
            ->latest()
            ->limit(5)
            ->get();
        
        // Recent feedbacks
        $recentFeedbacks = Feedback::with('user')
            ->latest()
            ->limit(5)
            ->get();
        
        // Recent notifications
        $recentNotifications = Notification::with('creator')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentBookings', 'recentFeedbacks', 'recentNotifications'));
    }
}
