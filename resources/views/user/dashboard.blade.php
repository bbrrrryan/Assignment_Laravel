@extends('layouts.app')

@section('title', 'Dashboard - TARUMT FMS')

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>My Dashboard</h1>
        <p>Welcome back, {{ auth()->user()->name }}!</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3>{{ $stats['my_bookings'] }}</h3>
                <p>My Bookings</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3>{{ $stats['active_bookings'] }}</h3>
                <p>Active Bookings</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stat-info">
                <h3>{{ $stats['unread_notifications'] }}</h3>
                <p>Unread Notifications</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <h3>{{ $stats['loyalty_points'] }}</h3>
                <p>Loyalty Points</p>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="dashboard-section">
            <div class="section-header">
                <h2>My Recent Bookings</h2>
                <a href="{{ route('bookings.index') }}" class="btn-primary">View All</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBookings as $booking)
                            <tr>
                                <td>{{ $booking->facility->name ?? '-' }}</td>
                                <td>{{ $booking->booking_date->format('M d, Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($booking->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('H:i') }}</td>
                                <td>
                                    <span class="badge badge-{{ $booking->status === 'approved' ? 'success' : ($booking->status === 'pending' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No bookings found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Notifications</h2>
                <a href="{{ route('notifications.index') }}" class="btn-primary">View All</a>
            </div>
            <div class="notifications-list">
                @forelse($recentNotifications as $notification)
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-{{ $notification->type === 'warning' ? 'exclamation-triangle' : ($notification->type === 'success' ? 'check-circle' : 'info-circle') }}"></i>
                        </div>
                        <div class="notification-content">
                            <h4>{{ $notification->title }}</h4>
                            <p>{{ Str::limit($notification->message, 100) }}</p>
                            <span class="notification-time">{{ $notification->pivot->created_at->diffForHumans() }}</span>
                        </div>
                        @if(!$notification->pivot->is_read)
                            <span class="badge badge-primary">New</span>
                        @endif
                    </div>
                @empty
                    <p class="text-center">No notifications</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
