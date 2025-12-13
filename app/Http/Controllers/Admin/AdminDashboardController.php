<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Feedback;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Certificate;

class AdminDashboardController extends AdminBaseController
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        // User Management Stats
        $stats['total_users'] = User::count();
        $stats['active_users'] = User::where('status', 'active')->count();
        $stats['inactive_users'] = User::where('status', 'inactive')->count();

        // Booking Stats
        $stats['total_bookings'] = Booking::count();
        $stats['pending_bookings'] = Booking::where('status', 'pending')->count();
        $stats['approved_bookings'] = Booking::where('status', 'approved')->count();

        // Facility Stats
        $stats['total_facilities'] = Facility::count();
        $stats['active_facilities'] = Facility::where('status', 'available')->count();
        $stats['maintenance_facilities'] = Facility::where('status', 'maintenance')->count();

        // Feedback Stats
        $stats['total_feedbacks'] = Feedback::count();
        $stats['pending_feedbacks'] = Feedback::where('status', 'pending')->count();
        $stats['blocked_feedbacks'] = Feedback::where('is_blocked', true)->count();

        // Loyalty Stats
        $stats['total_loyalty_points'] = LoyaltyPoint::sum('points') ?? 0;
        $stats['total_rewards'] = Reward::count();
        $stats['total_certificates'] = Certificate::count();

        // Recent Data
        $recentBookings = Booking::with(['user', 'facility'])->latest()->limit(5)->get();
        $recentFeedbacks = Feedback::with('user')->latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recentBookings', 'recentFeedbacks'));
    }
}
