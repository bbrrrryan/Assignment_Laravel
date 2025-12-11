<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 * User Dashboard Controller
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display user dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // User-specific stats
        $stats = [
            'my_bookings' => $user->bookings()->count(),
            'active_bookings' => $user->bookings()->whereIn('status', ['pending', 'approved'])->count(),
            'unread_notifications' => $user->notifications()->wherePivot('is_read', false)->count(),
            'loyalty_points' => $user->total_points ?? 0,
        ];

        // Recent bookings
        $recentBookings = $user->bookings()
            ->with('facility')
            ->latest()
            ->limit(5)
            ->get();

        // Recent notifications
        $recentNotifications = $user->notifications()
            ->where('is_active', true)
            ->latest('pivot_created_at')
            ->limit(5)
            ->get();

        return view('user.dashboard', compact('stats', 'recentBookings', 'recentNotifications'));
    }
}
