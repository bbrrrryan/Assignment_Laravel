{{-- Author: Liew Zi Li (profile management) --}}
@extends('layouts.app')

@section('title', 'My Profile - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>My Profile</h1>
    </div>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2 id="profileName">Loading...</h2>
                <p id="profileEmail">Loading...</p>
                <div style="display: flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap; margin-top: 10px;">
                    <p id="profileRole" class="role-badge">Loading...</p>
                    <p id="profileStudentId" class="role-badge" style="display: none;"></p>
                </div>
            </div>

            <div class="profile-content">
                <div class="profile-section">
                    <h3>Personal Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Name</label>
                            <input type="text" id="profileNameInput" class="form-control">
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <input type="email" id="profileEmailInput" class="form-control" readonly>
                        </div>
                        <div class="info-item">
                            <label>Phone Number</label>
                            <input type="text" id="profilePhoneInput" class="form-control">
                        </div>
                        <div class="info-item">
                            <label>Address</label>
                            <textarea id="profileAddressInput" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="info-item">
                            <label>Role</label>
                            <input type="text" id="profileRoleInput" class="form-control" readonly>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <input type="text" id="profileStatusInput" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h3>Change Password</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>New Password</label>
                            <input type="password" id="newPasswordInput" class="form-control" placeholder="Leave blank to keep current password">
                        </div>
                        <div class="info-item">
                            <label>Confirm New Password</label>
                            <input type="password" id="confirmPasswordInput" class="form-control" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="btn-primary" onclick="updateProfile()">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button class="btn-secondary" onclick="loadProfile()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    <link rel="stylesheet" href="{{ asset('css/profile/index.css') }}">
    <script src="{{ asset('js/profile/index.js') }}"></script>
</div>
</div>

<style>
.profile-container {
    max-width: 900px;
    margin: 0 auto;
}

.profile-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    text-align: center;
}

.profile-avatar {
    font-size: 80px;
    margin-bottom: 20px;
}

.profile-header h2 {
    margin: 10px 0;
    font-size: 1.8em;
}

.profile-header p {
    margin: 5px 0;
    opacity: 0.9;
}

.role-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    background: rgba(255,255,255,0.2);
    margin-top: 10px;
    font-size: 0.9em;
}

.role-badge.role-admin {
    background: rgba(255,193,7,0.3);
}

.role-badge.role-staff {
    background: rgba(33,150,243,0.3);
}

.role-badge.role-student {
    background: rgba(76,175,80,0.3);
}

.profile-content {
    padding: 30px;
}

.profile-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e0e0e0;
}

.profile-section:last-of-type {
    border-bottom: none;
}

.profile-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.3em;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-item label {
    font-weight: 600;
    color: #555;
    margin-bottom: 8px;
    font-size: 0.9em;
}

.form-control {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1em;
}

.form-control:read-only {
    background: #f5f5f5;
    cursor: not-allowed;
}

.profile-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-actions {
        flex-direction: column;
    }
}
</style>
@endsection

