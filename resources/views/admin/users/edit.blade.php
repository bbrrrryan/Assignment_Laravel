@extends('layouts.app')

@section('title', 'Edit User - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Edit User</h1>
            <p>Update user information</p>
        </div>
        <div>
            <a href="{{ route('admin.users.index') }}" class="btn-header-white">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">
            <h5><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h5>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
            @csrf
            @method('PUT')
            
            <div class="form-section">
                <h3><i class="fas fa-user"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="role">Role <span class="required">*</span></label>
                    @if(auth()->user()->isAdmin())
                        {{-- Admin can change role --}}
                        <select id="role" name="role" required class="form-select">
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin - Administrator</option>
                            <option value="student" {{ old('role', $user->role) === 'student' ? 'selected' : '' }}>Student - Student User</option>
                            <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff - Staff Member</option>
                        </select>
                    @else
                        {{-- Staff can only view role, cannot change --}}
                        <div class="role-display">
                            <span class="badge badge-info">
                                {{ ucfirst($user->role) }}
                            </span>
                            <input type="hidden" name="role" value="{{ $user->role }}">
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Only administrators can change user role. Current role: <strong>{{ ucfirst($user->role) }}</strong>
                        </small>
                    @endif
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-lock"></i> Password (Optional)</h3>
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-input">
                    <small>Leave blank to keep current password. Minimum 6 characters if changing.</small>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm New Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-input">
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
                
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" 
                           value="{{ old('phone_number', $user->phone_number) }}" class="form-input">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" class="form-input">{{ old('address', $user->address) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    @if(auth()->user()->isAdmin())
                        {{-- Admin can change status to active or inactive --}}
                        <select id="status" name="status" required class="form-select">
                            <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active - User can login</option>
                            <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive - User cannot login</option>
                        </select>
                        <small>Set to "Inactive" to prevent user from logging in. This is safer than deleting the user.</small>
                    @else
                        {{-- Staff can only view status, cannot change to inactive --}}
                        <div class="status-display">
                            <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                            <input type="hidden" name="status" value="{{ $user->status }}">
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Only administrators can change user status. Current status: <strong>{{ ucfirst($user->status) }}</strong>
                        </small>
                    @endif
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Update User
                </button>
            </div>
        </form>
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
}

.btn-header-white:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    color: #a01a2a;
}

/* Form Card Styling */
.form-card {
    background: #ffffff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 25px;
    border-bottom: 2px solid #f1f3f5;
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section h3 {
    font-size: 1.3rem;
    color: #495057;
    margin: 0 0 20px 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section h3 i {
    color: #cb2d3e;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #495057;
    font-weight: 600;
    font-size: 0.95rem;
}

.form-group .required {
    color: #dc3545;
}

.form-input,
.form-select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #495057;
    font-family: inherit;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #cb2d3e;
    box-shadow: 0 0 0 3px rgba(203, 45, 62, 0.1);
}

.form-input::placeholder {
    color: #adb5bd;
}

.form-select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    padding-right: 40px;
}

.form-group small {
    display: block;
    margin-top: 6px;
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.4;
}

.form-group small.text-muted {
    color: #6c757d;
}

.status-display,
.role-display {
    padding: 12px 15px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    display: inline-block;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.9rem;
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

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid #f1f3f5;
}

.btn-primary,
.btn-secondary {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-primary {
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    color: #ffffff;
    box-shadow: 0 4px 6px rgba(203, 45, 62, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(203, 45, 62, 0.4);
}

.btn-secondary {
    background: #ffffff;
    color: #6c757d;
    border: 2px solid #e9ecef;
}

.btn-secondary:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #495057;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Alert Styling */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    border: 1px solid;
}

.alert-error {
    background-color: #fee;
    border-color: #fcc;
    color: #c33;
}

.alert-error h5 {
    margin: 0 0 10px 0;
    font-size: 1rem;
    font-weight: 600;
}

.alert-error ul {
    margin: 0;
    padding-left: 20px;
}

.alert-error li {
    margin-bottom: 5px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    
    .page-header-content h1 {
        font-size: 1.8rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-primary,
    .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endsection
