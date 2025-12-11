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
                <p id="profileRole" class="role-badge">Loading...</p>
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

        <!-- Activity History Section -->
        <div class="profile-card" style="margin-top: 20px;">
            <div class="profile-content">
                <div class="profile-section">
                    <h3>Account Activity History</h3>
                    <div id="activityLogs" class="activity-logs">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i> Loading activity history...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    loadProfile();
    loadActivityLogs();
});

async function loadProfile() {
    const result = await API.get('/me');

    if (result.success) {
        const user = result.data.user;
        displayProfile(user);
    } else {
        alert('Error loading profile: ' + (result.error || 'Unknown error'));
    }
}

function displayProfile(user) {
    // Display header info
    document.getElementById('profileName').textContent = user.name || 'N/A';
    document.getElementById('profileEmail').textContent = user.email || 'N/A';
    
    const roleName = user.role ? (typeof user.role === 'object' ? user.role.name : user.role) : 'N/A';
    document.getElementById('profileRole').textContent = roleName.charAt(0).toUpperCase() + roleName.slice(1);
    document.getElementById('profileRole').className = 'role-badge role-' + roleName.toLowerCase();

    // Fill form fields
    document.getElementById('profileNameInput').value = user.name || '';
    document.getElementById('profileEmailInput').value = user.email || '';
    document.getElementById('profilePhoneInput').value = user.phone_number || '';
    document.getElementById('profileAddressInput').value = user.address || '';
    document.getElementById('profileRoleInput').value = roleName;
    document.getElementById('profileStatusInput').value = user.status || 'N/A';

    // Clear password fields
    document.getElementById('newPasswordInput').value = '';
    document.getElementById('confirmPasswordInput').value = '';
}

async function updateProfile() {
    const name = document.getElementById('profileNameInput').value;
    const phone = document.getElementById('profilePhoneInput').value;
    const address = document.getElementById('profileAddressInput').value;
    const newPassword = document.getElementById('newPasswordInput').value;
    const confirmPassword = document.getElementById('confirmPasswordInput').value;

    if (!name) {
        alert('Name is required');
        return;
    }

    if (newPassword && newPassword !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }

    const data = {
        name: name,
        phone_number: phone || null,
        address: address || null,
    };

    if (newPassword) {
        data.password = newPassword;
        data.password_confirmation = confirmPassword;
    }

    const result = await API.put('/users/profile/update', data);

    if (result.success) {
        alert('Profile updated successfully!');
        loadProfile();
        
        // Update localStorage user data
        const updatedUser = result.data.data;
        localStorage.setItem('user', JSON.stringify(updatedUser));
        
        // Update header name
        const headerName = document.querySelector('#authLinks span');
        if (headerName) {
            headerName.textContent = updatedUser.name || 'User';
        }
    } else {
        alert('Error updating profile: ' + (result.error || 'Unknown error'));
    }
}

async function loadActivityLogs(page = 1) {
    const activityLogsContainer = document.getElementById('activityLogs');
    if (!activityLogsContainer) return;

    try {
        activityLogsContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading activity history...</div>';

        const result = await API.get(`/users/profile/activity-logs?page=${page}`);

        if (result.success) {
            displayActivityLogs(result.data.data, result.data);
        } else {
            activityLogsContainer.innerHTML = `<div class="error-message">Error loading activity history: ${result.error || 'Unknown error'}</div>`;
        }
    } catch (error) {
        activityLogsContainer.innerHTML = `<div class="error-message">Error loading activity history: ${error.message}</div>`;
    }
}

function displayActivityLogs(logs, paginationData) {
    const activityLogsContainer = document.getElementById('activityLogs');
    if (!activityLogsContainer) return;

    if (!logs || logs.length === 0) {
        activityLogsContainer.innerHTML = '<div class="empty-message">No activity history found</div>';
        return;
    }

    let html = '<div class="activity-logs-list">';
    
    logs.forEach(log => {
        const actionClass = getActionBadgeClass(log.action);
        const dateTime = formatDateTime(log.created_at);
        
        html += `
            <div class="activity-log-item">
                <div class="activity-log-icon">
                    <i class="fas fa-${getActionIcon(log.action)}"></i>
                </div>
                <div class="activity-log-content">
                    <div class="activity-log-action">
                        <span class="badge badge-${actionClass}">${log.action}</span>
                    </div>
                    <div class="activity-log-description">${log.description || '-'}</div>
                    <div class="activity-log-time">${dateTime}</div>
                </div>
            </div>
        `;
    });

    html += '</div>';

    // Add pagination if needed
    if (paginationData && paginationData.last_page > 1) {
        html += '<div class="activity-logs-pagination">';
        
        // Previous button
        if (paginationData.current_page > 1) {
            html += `<button onclick="loadActivityLogs(${paginationData.current_page - 1})" class="pagination-btn">Previous</button>`;
        }
        
        // Page numbers
        for (let i = 1; i <= paginationData.last_page; i++) {
            if (i === paginationData.current_page) {
                html += `<span class="pagination-current">${i}</span>`;
            } else {
                html += `<button onclick="loadActivityLogs(${i})" class="pagination-btn">${i}</button>`;
            }
        }
        
        // Next button
        if (paginationData.current_page < paginationData.last_page) {
            html += `<button onclick="loadActivityLogs(${paginationData.current_page + 1})" class="pagination-btn">Next</button>`;
        }
        
        html += '</div>';
        html += `<div class="pagination-info">Showing ${paginationData.from}-${paginationData.to} of ${paginationData.total} records (last 30 only)</div>`;
    } else if (paginationData && paginationData.total > 0) {
        html += `<div class="pagination-info">Showing ${paginationData.total} record(s) (last 30 only)</div>`;
    }

    activityLogsContainer.innerHTML = html;
}

function getActionBadgeClass(action) {
    if (!action) return 'secondary';
    
    const actionLower = action.toLowerCase();
    if (actionLower.includes('create') || actionLower.includes('login')) return 'success';
    if (actionLower.includes('update') || actionLower.includes('edit')) return 'info';
    if (actionLower.includes('delete') || actionLower.includes('logout')) return 'danger';
    return 'secondary';
}

function getActionIcon(action) {
    if (!action) return 'circle';
    
    const actionLower = action.toLowerCase();
    if (actionLower.includes('login')) return 'sign-in-alt';
    if (actionLower.includes('logout')) return 'sign-out-alt';
    if (actionLower.includes('create')) return 'plus';
    if (actionLower.includes('update') || actionLower.includes('edit')) return 'edit';
    if (actionLower.includes('delete')) return 'trash';
    if (actionLower.includes('password')) return 'key';
    if (actionLower.includes('settings')) return 'cog';
    return 'circle';
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute(s) ago`;
    if (diffHours < 24) return `${diffHours} hour(s) ago`;
    if (diffDays < 7) return `${diffDays} day(s) ago`;
    
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

</script>

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

/* Activity Logs Styles */
.activity-logs {
    min-height: 200px;
}

.loading-spinner {
    text-align: center;
    padding: 40px;
    color: #666;
}

.error-message, .empty-message {
    text-align: center;
    padding: 40px;
    color: #999;
}

.activity-logs-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-log-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 6px;
    border-left: 3px solid #667eea;
}

.activity-log-icon {
    font-size: 20px;
    color: #667eea;
    display: flex;
    align-items: center;
}

.activity-log-content {
    flex: 1;
}

.activity-log-action {
    margin-bottom: 5px;
}

.activity-log-description {
    color: #333;
    margin-bottom: 5px;
    font-size: 0.95em;
}

.activity-log-time {
    color: #999;
    font-size: 0.85em;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

.activity-logs-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.pagination-btn {
    padding: 8px 15px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.pagination-btn:hover {
    background: #f0f0f0;
    border-color: #667eea;
}

.pagination-current {
    padding: 8px 15px;
    background: #667eea;
    color: white;
    border-radius: 4px;
    font-weight: 600;
}

.pagination-info {
    text-align: center;
    margin-top: 10px;
    color: #666;
    font-size: 0.9em;
}
</style>
@endsection

