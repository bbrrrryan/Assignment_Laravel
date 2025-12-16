{{-- Author: Liew Zi Li (create staff user) --}}
@extends('layouts.app')

@section('title', 'Add Staff - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Add Staff User</h1>
            <p>Create a new staff account for the system</p>
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
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            
            <div class="form-section">
                <h3><i class="fas fa-user"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Enter staff full name">
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-input" placeholder="Enter staff email">
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <div class="role-display">
                        <span class="badge badge-info">
                            Staff
                        </span>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> This form is only for creating <strong>Staff</strong> users. Role is fixed and cannot be changed here.
                    </small>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-lock"></i> Password</h3>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required class="form-input" placeholder="Enter password (min 6 characters)">
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input" placeholder="Re-type password">
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
                
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" 
                           value="{{ old('phone_number') }}" class="form-input" placeholder="Optional phone number">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" class="form-input" placeholder="Optional address">{{ old('address') }}</textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Create Staff User
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Reuse styles from edit page to keep consistent look --}}
<style>
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

.form-group small {
    display: block;
    margin-top: 6px;
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.4;
}

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


