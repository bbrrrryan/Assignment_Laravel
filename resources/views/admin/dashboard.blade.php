@extends('layouts.app')

@section('title', 'Admin Dashboard - TARUMT FMS')

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, {{ auth()->user()->name }}!</p>
    </div>

    <!-- User Management Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-users"></i> User Management Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_users'] }}</h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['active_users'] }}</h3>
                    <p>Active Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['suspended_users'] }}</h3>
                    <p>Suspended Users</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking & Scheduling Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-calendar-check"></i> Booking & Scheduling Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_bookings'] }}</h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['pending_bookings'] }}</h3>
                    <p>Pending Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['approved_bookings'] }}</h3>
                    <p>Approved Bookings</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Facility Management Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-building"></i> Facility Management Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_facilities'] }}</h3>
                    <p>Total Facilities</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['active_facilities'] }}</h3>
                    <p>Available Facilities</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['maintenance_facilities'] }}</h3>
                    <p>Under Maintenance</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Management Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-comment-dots"></i> Feedback Management Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_feedbacks'] }}</h3>
                    <p>Total Feedbacks</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['pending_feedbacks'] }}</h3>
                    <p>Pending Review</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['blocked_feedbacks'] }}</h3>
                    <p>Blocked Feedbacks</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loyalty Management Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-star"></i> Loyalty Management Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ number_format($stats['total_loyalty_points']) }}</h3>
                    <p>Total Points Issued</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_rewards'] }}</h3>
                    <p>Total Rewards</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_certificates'] }}</h3>
                    <p>Certificates Issued</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="dashboard-content">
        <!-- Recent Users -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Users</h2>
                <a href="{{ route('admin.users.index') }}" class="btn-primary">View All</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUsers as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ ucfirst($user->role ?? '-') }}</td>
                                <td>
                                    <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'warning' }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No users found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Bookings</h2>
                <a href="{{ route('bookings.index') }}" class="btn-primary">View All</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBookings as $booking)
                            <tr>
                                <td>{{ $booking->user->name }}</td>
                                <td>{{ $booking->facility->name ?? '-' }}</td>
                                <td>{{ $booking->booking_date->format('M d, Y') }}</td>
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

        <!-- Recent Feedbacks -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Feedbacks</h2>
                <a href="{{ route('feedbacks.index') }}" class="btn-primary">View All</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentFeedbacks as $feedback)
                            <tr>
                                <td>{{ $feedback->user->name ?? '-' }}</td>
                                <td>{{ Str::limit($feedback->subject ?? 'No subject', 30) }}</td>
                                <td>
                                    @if($feedback->rating)
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $feedback->rating)
                                                <i class="fas fa-star" style="color: #ffc107;"></i>
                                            @else
                                                <i class="far fa-star" style="color: #ddd;"></i>
                                            @endif
                                        @endfor
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($feedback->is_blocked)
                                        <span class="badge badge-danger">Blocked</span>
                                    @else
                                        <span class="badge badge-info">{{ ucfirst($feedback->status ?? 'pending') }}</span>
                                    @endif
                                </td>
                                <td>{{ $feedback->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No feedbacks found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
