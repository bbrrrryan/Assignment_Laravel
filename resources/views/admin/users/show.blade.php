@extends('layouts.app')

@section('title', 'User Details - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>User Details</h1>
            <p>View user information and activity</p>
        </div>
        <div>
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-primary">
                <i class="fas fa-edit"></i> Edit User
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    <div class="details-section">
        <div class="details-card">
            <h2>User Information</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <label>ID</label>
                    <p>{{ $user->id }}</p>
                </div>
                <div class="detail-item">
                    <label>Name</label>
                    <p>{{ $user->name }}</p>
                </div>
                <div class="detail-item">
                    <label>Email</label>
                    <p>{{ $user->email }}</p>
                </div>
                <div class="detail-item">
                    <label>Role</label>
                    <p>
                        <span class="badge badge-info">
                            {{ ucfirst($user->role ?? '-') }}
                        </span>
                    </p>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <p>
                        <span class="badge badge-{{ $user->status === 'active' ? 'success' : ($user->status === 'suspended' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </p>
                </div>
                <div class="detail-item">
                    <label>Phone Number</label>
                    <p>{{ $user->phone_number ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label>Address</label>
                    <p>{{ $user->address ?? '-' }}</p>
                </div>
                <div class="detail-item">
                    <label>Last Login</label>
                    <p>{{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}</p>
                </div>
                <div class="detail-item">
                    <label>Joined</label>
                    <p>{{ $user->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        <div class="activity-card">
            <h2>Recent Activity</h2>
            <div class="activity-list">
                @forelse($user->activityLogs as $log)
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-{{ $log->action === 'login' ? 'sign-in-alt' : ($log->action === 'logout' ? 'sign-out-alt' : 'circle') }}"></i>
                        </div>
                        <div class="activity-content">
                            <h4>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</h4>
                            <p>{{ $log->description ?? '-' }}</p>
                            <span class="activity-time">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-center">No activity found</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
