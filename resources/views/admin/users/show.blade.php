{{-- Author: Liew Zi Li (user show) --}}
@extends('layouts.app')

@section('title', 'User Details - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>User Details</h1>
            <p>View user information and activity</p>
        </div>
        <div>
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-header-white">
                <i class="fas fa-edit"></i> Edit User
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn-header-white">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    <div class="details-section">
        <div class="details-card">
            <h2><i class="fas fa-user"></i> User Information</h2>
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
                @if(($user->role === 'student' || $user->role === 'staff') && $user->personal_id)
                <div class="detail-item">
                    <label>{{ $user->role === 'student' ? 'Student ID' : 'Staff ID' }}</label>
                    <p>{{ $user->personal_id }}</p>
                </div>
                @endif
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
                        <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
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
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
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

<style>
/* Page Header Styling */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.page-header-content {
    padding: 10px 0;
}

.page-header-content h1 {
    font-size: 2.2rem;
    color: #ffffff;
    margin: 0 0 8px 0;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.page-header-content p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    margin: 0;
    font-weight: 400;
}

.btn-header-white {
    background-color: #ffffff;
    color: #cb2d3e; 
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-left: 10px;
}

.btn-header-white:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    color: #a01a2a;
}

/* Details Section */
.details-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.details-card,
.activity-card {
    background: #ffffff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.details-card h2,
.activity-card h2 {
    font-size: 1.5rem;
    color: #495057;
    margin: 0 0 25px 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f1f3f5;
}

.details-card h2 i,
.activity-card h2 i {
    color: #cb2d3e;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.detail-item p {
    margin: 0;
    color: #495057;
    font-size: 1rem;
    font-weight: 500;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-info {
    background-color: #e7f3ff;
    color: #0066cc;
}

.badge-success {
    background-color: #d4edda;
    color: #155724;
}

.badge-secondary {
    background-color: #e2e3e5;
    color: #383d41;
}

/* Activity List */
.activity-list {
    max-height: 500px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    margin-bottom: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #cb2d3e;
    transition: all 0.3s ease;
}

.activity-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.activity-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    color: #ffffff;
    border-radius: 50%;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-content h4 {
    margin: 0 0 5px 0;
    color: #495057;
    font-size: 1rem;
    font-weight: 600;
}

.activity-content p {
    margin: 0 0 8px 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.activity-time {
    font-size: 0.85rem;
    color: #adb5bd;
}

.text-center {
    text-align: center;
    color: #6c757d;
    padding: 20px;
}

/* Responsive Design */
@media (max-width: 968px) {
    .details-section {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    
    .page-header-content h1 {
        font-size: 1.8rem;
    }
    
    .btn-header-white {
        margin-left: 0;
        margin-top: 10px;
        width: 100%;
        justify-content: center;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
